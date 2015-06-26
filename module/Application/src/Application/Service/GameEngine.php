<?php

namespace Application\Service;

use Application\Model;
use Zend\Session\Container;
use Zend\Serializer\Adapter\PhpSerialize;

class GameEngine
{
    const ACTION_TWIST = 'twist';
    const ACTION_STICK = 'stick';

    const STATE_NOTSTARTED = 'notstarted'; // game is not started
    const STATE_CONTINUES  = 'continues'; // game is not finished yet
    const STATE_WIN        = 'win';  // the player won
    const STATE_LOOSE      = 'loose';  // the player has lost
    const STATE_DRAW       = 'draw';

    const MAX_SCORES_PLAYER = 21;
    const MAX_SCORES_DEALER = 17;

    /**
     * @var \Application\Model\Hand
     */
    private $player;

    /**
     * @var \Application\Model\Hand
     */
    private $dealer;

    /**
     * @var \Application\Model\GameSession
     */
    private $game;

    /**
     * @var \Zend\Session\Container
     */
    private $storage;

    /**
     * Tries to restore prev session or start a new one
     */
    public function __construct()
    {
        $this->restore();
    }

    /**
     * Initialize new game if prev one was not restored
     *
     * @return void
     */
    public function init()
    {
        if (null == $this->game) {
            $this->start();
        }
    }

    /**
     * Reset the current game session
     *
     * @return void
     */
    public function reset()
    {
        $this->game = null;
        $this->getStorage()->state = self::STATE_NOTSTARTED;

        return $this;
    }

    /**
     * Process turn
     *
     * @param  string $action
     * @return void
     */
    public function turn($action)
    {
        switch ($action) {
            case self::ACTION_TWIST:
                $this->game->addCard($this->player);
                if ($this->player->getScores() >= self::MAX_SCORES_PLAYER) {
                    // do nothing if the player has lost or won
                    $this->dealer->complete();
                } else {
                    // or deal a new card
                    $this->game->addCard($this->dealer);
                }
                break;
            case self::ACTION_STICK:
            default:
                $this->player->complete();
                // the player stopped, so lets get the missing cards
                while (!$this->dealer->isCompleted()) {
                    $this->game->addCard($this->dealer);
                }

                break;
        }

        // save current state
        $this->save();

        // finish the game session and add the result to the history and statistic
        if (!$this->isContinues()) {
            return $this->finish();
        }

        return $this->getState();
    }

    /**
     * Returns current state of the game
     *
     * @return integer
     */
    public function getState()
    {
        if (!$this->isStarted()) {
            return self::STATE_NOTSTARTED;
        }

        $result = self::STATE_CONTINUES;
        if ($this->game->isFinished()) {
            $pscores = $this->player->getScores();
            $dscores = $this->dealer->getScores();

            $result = self::STATE_LOOSE;
            if (
                // the best score
                $pscores == self::MAX_SCORES_PLAYER ||
                // more than the dealer or dealer has more than allowed
                ($pscores < self::MAX_SCORES_PLAYER && ($pscores > $dscores || $dscores > self::MAX_SCORES_PLAYER))
            ) {
                $result = self::STATE_WIN;
            } else if ($dscores == $pscores) {
                $result = self::STATE_DRAW;
            }
        }

        return $result;
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return null !== $this->game;
    }

    /**
     * @return boolean
     */
    public function isContinues()
    {
        return $this->isStarted() && self::STATE_CONTINUES == $this->getState();
    }

    /**
     * @return \Application\Model\Hand
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return \Application\Model\Hand
     */
    public function getDealer()
    {
        return $this->dealer;
    }

    /**
     * Prepares and returns player's statistic
     *
     * @return array
     */
    public function getStats()
    {
        $stats = array(
            'wins'    => 0,
            'looses'  => 0,
            'draws'   => 0,
            'total'   => 0,
        );

        $history = $this->getHistory();
        foreach ($history as $result) {
            $result = $result[2];
            if ($result === self::STATE_WIN) {
                $stats['wins']++;
            } elseif ($result === self::STATE_LOOSE) {
                $stats['looses']++;
            } else {
                $stats['draws']++;
            }
        }
        $stats['total'] = count($history);

        return $stats;
    }

    /**
     * Returns game history
     *
     * @return array
     */
    public function getHistory()
    {
        if (!is_array($this->getStorage()->history)) {
            $this->getStorage()->history = array();
        }

        return $this->getStorage()->history;
    }

    /**
     * Add the result to the history
     *
     * @param  integer $pscores Player's scores
     * @param  integer $dscores Dealer's scores
     * @param  string  $result  Result of the game
     * @return void
     */
    protected function addHistory($pscores, $dscores, $result)
    {
        if (!is_array($this->getStorage()->history)) {
            $this->getStorage()->history = array();
        }

        array_unshift($this->getStorage()->history, array($pscores, $dscores, $result));
    }

    /**
     * Saved the result and stats of the current game and resets the game session
     *
     * @return integer Code of the result state
     */
    protected function finish()
    {
        $state = $this->getState();
        // add the result to the history
        $this->addHistory(
            $this->getPlayer()->getScores(),
            $this->getDealer()->getScores(),
            $state
        );
        // reset current game
        $this->reset();

        return $state;
    }

    /**
     * Starts new game session
     *
     * @return void
     */
    protected function start()
    {
        $this->player = new Model\Hand(self::MAX_SCORES_PLAYER);
        $this->dealer = new Model\Hand(self::MAX_SCORES_DEALER, true);

        $this->game = new Model\GameSession(array($this->dealer, $this->player));

        // alternately take two cards
        $this->game->addCard($this->dealer);
        $this->game->addCard($this->player);
        $this->game->addCard($this->dealer);
        $this->game->addCard($this->player);

        $this->save();
    }

    /**
     * Restore prev game session
     *
     * @return boolean TRUE if prev session exist and nto finished
     */
    protected function restore()
    {
        if ($this->getStorage()->state !== self::STATE_CONTINUES) {
            return false;
        }

        $serializer = new PhpSerialize();
        $this->player = $serializer->unserialize($this->getStorage()->player);
        $this->dealer = $serializer->unserialize($this->getStorage()->dealer);

        $this->game = new Model\GameSession(array($this->dealer, $this->player));
        $this->game->setDeck($this->getStorage()->deck);

        return true;
    }

    /**
     * Save current state in storage
     *
     * @return void
     */
    protected function save()
    {
        $serializer = new PhpSerialize();
        $this->getStorage()->player = $serializer->serialize($this->player);
        $this->getStorage()->dealer = $serializer->serialize($this->dealer);
        $this->getStorage()->deck   = $this->game->getDeck();
        $this->getStorage()->state  = $this->getState();
    }

    /**
     * @return \Zend\Session\Container
     */
    protected function getStorage()
    {
        if (null === $this->storage) {
            $this->storage = new Container('game');
        }

        return $this->storage;
    }
} 