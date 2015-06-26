<?php

namespace Application\Model;

class Hand
{
    /**
     * List of the current cards
     *
     * @var array
     */
    private $cards = array();

    /**
     * Current scores
     *
     * @var integer
     */
    private $scores = 0;

    /**
     * Max scores (when the game automatically stops for this hand)
     *
     * @var integer
     */
    private $maxScores;

    /**
     * Is the hand completed or not
     *
     * @var boolean
     */
    private $completed = false;

    /**
     * Is it the dealer or real player
     *
     * @var boolean
     */
    private $isDealer = false;

    /**
     * @param integer $maxScores
     */
    public function __construct($maxScores = 21, $isDealer = false)
    {
        $this->maxScores = $maxScores;
        $this->isDealer  = $isDealer;
    }

    /**
     * Add new card
     *
     * @param  string  $card Code of the card
     * @param  integer $scores Value of the card
     * @return \Application\Model\Hand $this
     */
    public function addCard($card, $scores)
    {
        $this->cards[] = array($card, $scores);
        $this->scores += $scores;

        // force stop the game
        if ($this->scores >= $this->maxScores) {
            $this->complete();
        }

        return $this;
    }

    /**
     * @return \Application\Model\Hand $this
     */
    public function complete()
    {
        $this->completed = true;

        return $this;
    }

    /**
     * @return array
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * @return integer
     */
    public function getScores()
    {
        return $this->scores;
    }

    /**
     * @return boolean
     */
    public function isCompleted()
    {
        return $this->completed;
    }

    /**
     * @return boolean
     */
    public function isDealer()
    {
        return $this->isDealer;
    }
} 