<?php

namespace Application\Model;

class GameSession
{
    /**
     * List of hands
     *
     * @var array
     */
    private $hands = array();

    /**
     * Cards with it scores
     *
     * @var array
     */
    private $cards = array(
        // clubs
        'ca' => 11, 'cj' => 10, 'cq' => 10, 'ck' => 10, 'c10' => 10, 'c2' => 2, 'c3' => 3, 'c4' => 4, 'c5' => 5, 'c6' => 6, 'c7' => 7, 'c8' => 8, 'c9' => 9,
        // diamonds
        'da' => 11, 'dj' => 10, 'dq' => 10, 'dk' => 10, 'd10' => 10, 'd2' => 2, 'd3' => 3, 'd4' => 4, 'd5' => 5, 'd6' => 6, 'd7' => 7, 'd8' => 8, 'd9' => 9,
        // hearts
        'ha' => 11, 'hj' => 10, 'hq' => 10, 'hk' => 10, 'h10' => 10, 'h2' => 2, 'h3' => 3, 'h4' => 4, 'h5' => 5, 'h6' => 6, 'h7' => 7, 'h8' => 8, 'h9' => 9,
        // spades
        'sa' => 11, 'sj' => 10, 'sq' => 10, 'sk' => 10, 's10' => 10, 's2' => 2, 's3' => 3, 's4' => 4, 's5' => 5, 's6' => 6, 's7' => 7, 's8' => 8, 's9' => 9
    );

    /**
     * Current playing deck
     *
     * @var array
     */
    private $deck;

    /**
     * Initialize new game
     *
     * @param array   $hands List of the participants
     * @throw \InvalidArgumentException if list of player is empty
     */
    public function __construct(array $hands)
    {
        if (count($hands) == 0) {
            throw new \InvalidArgumentException("List of players cannot by empty");
        }

        // prepare deck
        $this->deck = array_keys($this->cards);
        shuffle($this->deck);

        $this->hands = $hands;
    }

    /**
     * @param  \Application\Model\Hand $hand
     * @return \Application\Model\GameSession
     */
    public function addCard(Hand $hand)
    {
        if (!$hand->isCompleted() && !$this->isFinished()) {
            $card  = array_shift($this->deck);
            $value = $this->cards[$card];
            $hand->addCard($card, $value);
        }

        return $this;
    }

    /**
     * Checks the state of all hands
     *
     * @return boolean
     */
    public function isFinished()
    {
        $finished = true;
        /** @var $hand \Application\Model\Hand */
        foreach ($this->hands as $hand) {
            if (!$hand->isCompleted()) {
                $finished = false;
                break;
            }
        }

        return $finished;
    }

    /**
     * @return array
     */
    public function getHands()
    {
        return $this->hands;
    }

    /**
     * @return array
     */
    public function getDeck()
    {
        return $this->deck;
    }

    /**
     * @param  array $deck
     * @return \Application\Model\GameSession $this
     */
    public function setDeck(array $deck)
    {
        $this->deck = $deck;

        return $this;
    }
} 