<?php

namespace Application\Controller;

use Application\Service\GameEngine;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    /**
     * @var \Application\Service\GameEngine
     */
    private $engine;

    public function __construct()
    {
        $this->engine = new GameEngine();
    }

    public function indexAction()
    {
        return new ViewModel(array(
            'dealer' => $this->getDealerData(),
            'player' => $this->getHandData($this->engine->getPlayer()),
            'state'  => $this->getStateData($this->engine->getState()),
            'stats'  => $this->getStatsData()
        ));
    }

    /**
     * Returns table with history
     */
    public function historyAction()
    {
        $renderer = $this->getServiceLocator()->get('ViewRenderer');
        $view = new ViewModel(array('history' => $this->getHistoryData()));
        $view->setTemplate('application/index/history');

        return new JsonModel(array('html' => $renderer->render($view)));
    }

    /**
     * Resets current game
     */
    public function resetAction()
    {
        $this->engine->reset();

        return new JsonModel(array('state' => $this->getStateData($this->engine->getState())));
    }

    /**
     * Start new game or continue prev
     */
    public function playAction()
    {
        if (!$this->engine->isStarted()) {
            $this->engine->init();
        }

        $response = array(
            'dealer' => $this->getDealerData(),
            'player' => $this->getHandData($this->engine->getPlayer()),
            'state'  => $this->getStateData($this->engine->getState()),
            'stats'  => $this->getStatsData()
        );

        return new JsonModel($response);
    }

    /**
     * Process one turn of the game depends on user action (twist or stick)
     */
    public function turnAction()
    {
        if (!$this->engine->isStarted()) {
            return new JsonModel(array('error' => $this->getServiceLocator()->get('translator')->translate('Game is not started')));
        }
        $state = $this->engine->turn($this->params()->fromRoute('act'));

        $response = array(
            'dealer' => $this->getDealerData(),
            'player' => $this->getHandData($this->engine->getPlayer()),
            'state'  => $this->getStateData($state),
            'stats'  => $this->getStatsData()
        );

        return new JsonModel($response);
    }

    /**
     * Returns prepared dealer's data
     *
     * @return array
     */
    protected function getDealerData()
    {
        $data = $this->getHandData($this->engine->getDealer());

        // remove the last card before end of game
        if ($this->engine->isContinues()) {
            $last = array_pop($data['cards']);
            $data['scores'] -= $last[1];
            // add blank instead
            $data['cards'][] = array('blank',0);
        }

        return $data;
    }

    /**
     * @param  \Application\Model\Hand $hand
     * @return array
     */
    protected function getHandData($hand)
    {
        if (null == $hand) {
            return array('cards' => array(), 'scores' => 0);
        }

        return array(
            'cards'  => $hand->getCards(),
            'scores' => $hand->getScores()
        );
    }

    /**
     * @param  string $state Code of the current state
     * @return array State title and text
     */
    protected function getStateData($state)
    {
        $translator = $this->getServiceLocator()->get('translator');
        $title = $this->stateToText($state);
        $text  = $translator->translate('Click \'Play\' to start');

        return (object) array(
            'code'  => $state,
            'title' => $title,
            'text'  => $text
        );
    }

    /**
     * Converts code of the state to text
     *
     * @param  string $state
     * @return null
     */
    protected function stateToText($state)
    {
        $translator = $this->getServiceLocator()->get('translator');
        $text = null;
        switch ($state) {
            case GameEngine::STATE_NOTSTARTED:
                $text = $translator->translate('Welcome to Blackjack');
                break;
            case GameEngine::STATE_WIN:
                $text = $translator->translate('You WIN');
                break;
            case GameEngine::STATE_LOOSE:
                $text = $translator->translate('You LOOSE');
                break;
            case GameEngine::STATE_DRAW:
                $text = $translator->translate('Draw');
                break;
        }

        return $text;
    }

    /**
     * Returns prepared stats data
     *
     * @return object
     */
    protected function getStatsData()
    {
        $stats = (object) $this->engine->getStats();
        // percentage ratio
        $stats->pwins   = 0;
        $stats->plooses = 0;
        $stats->pdraws  = 0;
        if ($stats->total == 0) {
            return $stats;
        }

        $stats->pwins   = round($stats->wins / $stats->total * 100);
        $stats->plooses = round($stats->looses / $stats->total * 100);
        $stats->pdraws  = round($stats->draws / $stats->total * 100);

        return $stats;
    }

    /**
     * Prepare and returns data for scores table
     *
     * @return object
     */
    protected function getHistoryData()
    {
        $history = $this->engine->getHistory();
        foreach ($history as &$result) {
            $result = (object) array(
                'pscores' => $result[0],
                'dscores' => $result[1],
                'state'   => $this->stateToText($result[2])
            );
        }

        return $history;
    }
}
