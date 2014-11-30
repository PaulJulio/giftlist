<?php
namespace gift;
if (!class_exists('\\' . __NAMESPACE__ . '\\Loader', false)) {
    require_once(realpath(__DIR__) . '/loader.php');
}

$groups = 0; // members of a group can not buy a gift for another member of the group
$list =  array(); // todo: allow for this list to be uploaded
$hash = md5_file(__DIR__ . '/list.csv');
$fh = fopen(__DIR__ . '/list.csv', 'r');
while (($fl = fgetcsv($fh)) !== false) {
    foreach($fl as $item) {
        $info = explode(':', trim($item)); // The Name:email@tld.com
        $giver = new Giver();
        $giver->setName($info[0]);
        $giver->setEmail($info[1]);
        $giver->setGroup($groups);
        $list[] = $giver;
    }
    ++$groups;
}

printf('Loaded %s givers in %s groups%s', count($list), $groups, PHP_EOL);
$tries = 1;
$success = false;
while ($tries < 25) {
    // seed the random number generator with 8 hex digits taken from the end of the md5 hash
    // on each try, we shift the starting position left one character in the hash
    $hex = '0x' . substr($hash, (-7 - $tries), 8);
    printf('Seed for try #%s: %s%s', $tries++, $hex, PHP_EOL);
    srand(hexdec($hex));
    // get copy of our list by value
    $l = unserialize(serialize($list));
    $i = rand(0, count($l) - 1);
    /* @var Giver $start */
    $start = array_pop(array_splice($l, $i, 1));
    while (count($l)) {
        if (count($l) == 1) {
            // the last element needs to have a different group than both the head and the tail
            /* @var Giver $last */
            $last = array_pop($l);
            /* @var Giver $head */
            $head = $start->getHead();
            /* @var Giver $tail */
            $tail = $start->getTail();
            if ($head === true || $tail === true) {
                throw new \Exception('Unexpected Circular List Before Last Item Added');
            }
            if ($last->getGroup() == $head->getGroup() || $last->getGroup() == $tail->getGroup()) {
                printf('Could not insert %s between %s and %s because of group collision. Trying again.%s', $last->getName(), $head->getName(), $tail->getName(), PHP_EOL);
                continue 2;
            }
            // ok, the list has been created
            // todo: send emails. for now, I'm outputting the list
            printf($head->getName() . ' -> ');
            $next = $head;
            while ($next = $next->getGiveTo()) {
                printf($next->getName() . ' -> ');
            }
            printf('%s %s', $last->getName(), PHP_EOL);
            $tail->setGiveTo($last);
            $last->setGetFrom($tail);
            $last->setGiveTo($head);
            $head->setGetFrom($last);
            $head->setStartHere();
            $success = true;
            break 2;
        }
        $i = rand(0, count($l) - 1);
        /* @var Giver $pick */
        $pick = array_pop(array_splice($l, $i, 1));
        $head = $start->getHead();
        $tail = $start->getTail();
        if ($head === true || $tail === true) {
            throw new \Exception('Unexpected Circular List Before Last Item Added');
        }
        // first try to add pick to tail
        if ($pick->getGroup() != $tail->getGroup()) {
            $tail->setGiveTo($pick);
            $pick->setGetFrom($tail);
        } elseif ($pick->getGroup() != $head->getGroup()) {
            $pick->setGiveTo($head);
            $head->setGetFrom($pick);
        } else {
            // cannot insert into the chain, put it back at the end of the list
            array_push($l, $pick);
            // check to make sure that we have more than one group in the list
            $allsame = true;
            /* @var Giver $v */
            foreach ($l as $v) {
                if ($pick->getGroup() != $v->getGroup()) {
                    $allsame = false;
                }
            }
            if ($allsame) {
                printf('Could not insert %s between %s an %s because of group collision, and all remaining list members are in the same group. Trying again.%s', $pick->getName(), $head->getName(), $tail->getName(), PHP_EOL);
                continue 2;
            }
        }
    }
}
if ($success) {
    // loop over circular list
    $node = $head; // todo: probably shouldn't rely on the $head variable above
    $text = '';
    do {
        printf('%s gets an email to give a gift to %s%s', $node->getName(), $node->getGiveTo()->getName(), PHP_EOL);
        $text .= sprintf('%s gets an email to give a gift to %s%s', $node->getName(), $node->getGiveTo()->getName(), "\r\n");
        $node = $node->getGiveTo();
    } while (!$node->isStartHere());
    $ses = \Aws\Ses\SesClient::factory(array(
        'key'    => \util\Settings::get('aws/key'),
        'secret' => \util\Settings::get('aws/secret'),
        'region' => \util\Settings::get('aws/sesregion'),
    ));
    $ses->sendEmail(array(
        'Source' => \util\Settings::get('email/from'),
        'Destination' => array(
            'ToAddresses' => array(
                'paulhulett@gmail.com'
            ),
        ),
        'Message' => array(
            'Subject' => array(
                'Data' => \util\Settings::get('email/subject'),
            ),
            'Body' => array(
                'Text' => array(
                    'Data' => $text,
                ),
            ),
        ),
    ));
} else {
    printf('Could not find any successful lists after 24 tries%s', PHP_EOL);
}
