<?php

namespace Crm\ScenariosModule\Presenters;

use Crm\ApiModule\Token\InternalToken;
use Crm\ApplicationModule\Components\VisualPaginator;
use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\OneSignalModule\Events\OneSignalNotificationEvent;
use Crm\ScenariosModule\Events\BannerEvent;
use Crm\ScenariosModule\Repository\ScenariosRepository;
use Nette\Application\BadRequestException;
use Nette\Application\LinkGenerator;

class ScenariosAdminPresenter extends AdminPresenter
{
    private $scenariosRepository;

    private $internalToken;

    private $bannerEnabled;

    private $linkGenerator;

    public function __construct(
        ScenariosRepository $scenariosRepository,
        InternalToken $internalToken,
        LinkGenerator $linkGenerator
    ) {
        parent::__construct();

        $this->scenariosRepository = $scenariosRepository;
        $this->internalToken = $internalToken;
        $this->linkGenerator = $linkGenerator;
    }

    public function renderDefault()
    {
        $products = $this->scenariosRepository->all();

        $filteredCount = $this->template->filteredCount = $products->count('*');

        $vp = new VisualPaginator();
        $this->addComponent($vp, 'scenarios_vp');
        $paginator = $vp->getPaginator();
        $paginator->setItemCount($filteredCount);
        $paginator->setItemsPerPage($this->onPage);

        $this->template->vp = $vp;
        $this->template->scenarios = $products->limit($paginator->getLength(), $paginator->getOffset());
    }

    public function renderEdit($id)
    {
        $scenario = $this->scenariosRepository->find($id);
        if (!$scenario) {
            $this->flashMessage($this->translator->translate('scenarios.admin.scenarios.messages.scenario_not_found'));
            $this->redirect('default');
        }
        $this->template->scenario = $scenario;
    }

    public function renderNew()
    {
    }

    public function renderEmbed($id)
    {
        // Enable Banner element in ScenarioBuilder if BannerEvent has handlers (so it can be processed)
        // DO NOT move this to constructor, listener might not have been added yet
        $this->template->bannerEnabled = $this->emitter->hasListeners(BannerEvent::class);

        // Enable Push notification element in ScenarioBuilder if OneSignalNotificationEvent has handlers (so it can be processed)
        // DO NOT move this to constructor, listener might not have been added yet
        $this->template->pushNotificationEnabled = $this->emitter->hasListeners(OneSignalNotificationEvent::class);

        $this->template->crmHost = $this->getHttpRequest()->getUrl()->getBaseUrl();
        $this->template->apiToken = 'Bearer ' . $this->internalToken->tokenValue();

        $this->template->scenario = $this->scenariosRepository->find($id);
    }

    public function handleEnable($id)
    {
        $scenario = $this->scenariosRepository->find($id);
        if (!$scenario) {
            throw new BadRequestException("unable to load scenario: " . $id);
        }
        $this->scenariosRepository->setEnabled($scenario);
        $this->flashMessage($this->translator->translate(
            'scenarios.admin.scenarios.messages.scenario_enabled',
            [
                '%name%' => $scenario->name,
            ]
        ));
        $this->redirect('default');
    }

    public function handleDisable($id)
    {
        $scenario = $this->scenariosRepository->find($id);
        if (!$scenario) {
            throw new BadRequestException("unable to load scenario: " . $id);
        }
        $this->scenariosRepository->setEnabled($scenario, false);
        $this->flashMessage($this->translator->translate(
            'scenarios.admin.scenarios.messages.scenario_disabled',
            [
                '%name%' => $scenario->name,
            ]
        ));
        $this->redirect('default');
    }
}
