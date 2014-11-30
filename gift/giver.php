<?php
namespace gift;
// pretty sure this is a Mac-only issue
if (!class_exists('\\' . __NAMESPACE__ . '\\Loader', false)) {
    require_once(__DIR__ . '/loader.php');
}

class Giver {
    /* @var string $name */
    private $name;
    /* @var string $email */
    private $email;
    /* @var int $group */
    private $group;
    /* @var Giver $giveTo */
    private $giveTo;
    /* @var Giver $getFrom */
    private $getFrom;
    /* @var boolean $startHere */
    private $startHere = false;

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param int $group
     */
    public function setGroup($group) {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return Giver
     */
    public function getGetFrom() {
        return $this->getFrom;
    }

    /**
     * @param $getFrom
     * @throws \Exception
     */
    public function setGetFrom($getFrom) {
        if ($this->getGroup() == $getFrom->getGroup()) {
            throw new \Exception('Cannot get from member of same group');
        }
        $this->getFrom = $getFrom;
    }

    /**
     * @return Giver
     */
    public function getGiveTo() {
        return $this->giveTo;
    }

    /**
     * @param $giveTo
     * @throws \Exception
     */
    public function setGiveTo($giveTo) {
        if ($this->getGroup() == $giveTo->getGroup()) {
            throw new \Exception('Cannot give to member of same group');
        }
        $this->giveTo = $giveTo;
    }

    /**
     * @param Giver $firstCalled
     * @return $this|bool
     *
     * The head is the first giver. Returns true if the list is circular.
     */
    public function getHead(Giver $firstCalled = null) {
        if (isset($firstCalled)) {
            if ($firstCalled == $this) {
                return true;
            }
        } else {
            $firstCalled = $this;
        }
        if (isset($this->getFrom)) {
            return $this->getFrom->getHead($firstCalled);
        }
        return $this;
    }

    /**
     * @param Giver $firstCalled
     * @return $this|bool
     *
     * The tail is the last recipient. Returns true if the list is circular.
     */
    public function getTail(Giver $firstCalled = null) {
        if (isset($firstCalled)) {
            if ($firstCalled === $this) {
                return true;
            }
        } else {
            $firstCalled = $this;
        }
        if (isset($this->giveTo)) {
            return $this->giveTo->getTail($firstCalled);
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isStartHere() {
        return $this->startHere;
    }

    /**
     * @param boolean $startHere
     */
    public function setStartHere($startHere = true) {
        $this->startHere = $startHere;
    }

    /**
     * @return $this
     *
     * A successful list is circular, and so it needs a starting/ending point for searches.
     * If the list isn't circular,  we start with the head
     */
    public function getStartingGiver($circularCheck = false) {
        if (!$circularCheck) {
            $isCircular = $this->getGiveTo();
            if ($isCircular !== true) {
                return $this->getHead();
            }
        }
        if ($this->isStartHere()) {
            return $this;
        }
        return $this->getGiveTo()->getStartingGiver(true);
    }
}