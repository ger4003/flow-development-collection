<?php
namespace Neos\FluidAdaptor\View;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Utility\Files;
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;

/**
 * A standalone template view.
 * Helpful if you want to use Fluid separately from MVC
 * E.g. to generate template based emails.
 *
 * @api
 */
class StandaloneView extends AbstractTemplateView
{
    /**
     * Source code of the Fluid template
     * @var string
     */
    protected $templateSource = null;

    /**
     * absolute path of the Fluid template
     * @var string
     */
    protected $templatePathAndFilename = null;

    /**
     * absolute root path of the folder that contains Fluid layouts
     * @var string
     */
    protected $layoutRootPath = null;

    /**
     * absolute root path of the folder that contains Fluid partials
     * @var string
     */
    protected $partialRootPath = null;

    /**
     * @var \TYPO3\Flow\Utility\Environment
     * @Flow\Inject
     */
    protected $environment;

    /**
     * @var \TYPO3\Flow\Mvc\FlashMessageContainer
     * @Flow\Inject
     */
    protected $flashMessageContainer;

    /**
     * @var ActionRequest
     */
    protected $request;

    /**
     * Factory method to create an instance with given options.
     *
     * @param array $options
     * @return StandaloneView
     */
    public static function createWithOptions(array $options)
    {
        return new static(null, $options);
    }

    /**
     * Constructor
     *
     * @param ActionRequest $request The current action request. If none is specified it will be created from the environment.
     * @param array $options
     */
    public function __construct(ActionRequest $request = null, array $options = [])
    {
        $this->request = $request;
        parent::__construct($options);
    }

    /**
     * Initiates the StandaloneView by creating the required ControllerContext
     *
     * @return void
     */
    public function initializeObject()
    {
        if ($this->request === null) {
            $httpRequest = Request::createFromEnvironment();
            $this->request = new ActionRequest($httpRequest);
        }

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($this->request);

        $this->setControllerContext(new ControllerContext(
            $this->request,
            new Response(),
            new Arguments(array()),
            $uriBuilder
        ));
    }

    /**
     * @param string $templateName
     */
    public function setTemplate($templateName)
    {
        $this->baseRenderingContext->setControllerAction($templateName);
    }

    /**
     * Sets the format of the current request (default format is "html")
     *
     * @param string $format
     * @return void
     * @api
     */
    public function setFormat($format)
    {
        $this->baseRenderingContext->getControllerContext()->getRequest()->setFormat($format);
    }

    /**
     * Returns the format of the current request (defaults is "html")
     *
     * @return string $format
     * @api
     */
    public function getFormat()
    {
        return $this->baseRenderingContext->getControllerContext()->getRequest()->getFormat();
    }

    /**
     * Returns the current request object
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->baseRenderingContext->getControllerContext()->getRequest();
    }

    /**
     * Sets the absolute path to a Fluid template file
     *
     * @param string $templatePathAndFilename Fluid template path
     * @return void
     * @api
     */
    public function setTemplatePathAndFilename($templatePathAndFilename)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplatePathAndFilename($templatePathAndFilename);

        $partialRootPaths = $this->baseRenderingContext->getTemplatePaths()->getPartialRootPaths();
        $layoutRootPaths = $this->baseRenderingContext->getTemplatePaths()->getLayoutRootPaths();
        array_unshift($partialRootPaths, Files::concatenatePaths([dirname($templatePathAndFilename), 'Partials']));
        array_unshift($layoutRootPaths, Files::concatenatePaths([dirname($templatePathAndFilename), 'Layouts']));
        $this->baseRenderingContext->getTemplatePaths()->setPartialRootPaths($partialRootPaths);
        $this->baseRenderingContext->getTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
    }

    /**
     * Returns the absolute path to a Fluid template file if it was specified with setTemplatePathAndFilename() before
     *
     * @return string Fluid template path
     * @api
     */
    public function getTemplatePathAndFilename()
    {
        return $this->baseRenderingContext->getTemplatePaths()->getTemplatePathAndFilename();
    }

    /**
     * Sets the Fluid template source
     * You can use setTemplatePathAndFilename() alternatively if you only want to specify the template path
     *
     * @param string $templateSource Fluid template source code
     * @return void
     * @api
     */
    public function setTemplateSource($templateSource)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplateSource($templateSource);
    }

    /**
     * Set the root path(s) to the templates.
     *
     * @param string[] $templateRootPaths Root paths to the templates.
     * @return void
     * @api
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplateRootPaths($templateRootPaths);
    }

    /**
     * Set the root path(s) to the layouts.
     *
     * @param string[] $layoutRootPaths Root path to the layouts
     * @return void
     * @api
     */
    public function setLayoutRootPaths(array $layoutRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
    }

    /**
     * Resolves the layout root to be used inside other paths.
     *
     * @return string Fluid layout root path
     * @throws InvalidTemplateResourceException
     * @api
     */
    public function getLayoutRootPaths()
    {
        return $this->baseRenderingContext->getTemplatePaths()->getLayoutRootPaths();
    }

    /**
     * Sets the absolute path to the folder that contains Fluid layout files
     *
     * @param string $layoutRootPath Fluid layout root path
     * @return void
     * @api
     */
    public function setLayoutRootPath($layoutRootPath)
    {
        $this->baseRenderingContext->getTemplatePaths()->setLayoutRootPaths([$layoutRootPath]);
    }

    /**
     * Set the root path(s) to the partials.
     * If set, overrides the one determined from $this->partialRootPathPattern
     *
     * @param string[] $partialRootPaths Root paths to the partials. If set, overrides the one determined from $this->partialRootPathPattern
     * @return void
     * @api
     */
    public function setPartialRootPaths(array $partialRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setPartialRootPaths($partialRootPaths);
    }

    /**
     * Returns the absolute path to the folder that contains Fluid partial files
     *
     * @return string Fluid partial root path
     * @throws InvalidTemplateResourceException
     * @api
     */
    public function getPartialRootPaths()
    {
        return $this->baseRenderingContext->getTemplatePaths()->getPartialRootPaths();
    }

    /**
     * Sets the absolute path to the folder that contains Fluid partial files.
     *
     * @param string $partialRootPath Fluid partial root path
     * @return void
     * @api
     */
    public function setPartialRootPath($partialRootPath)
    {
        $this->baseRenderingContext->getTemplatePaths()->setPartialRootPaths([$partialRootPath]);
    }

    /**
     * Checks whether a template can be resolved for the current request
     *
     * @return bool
     * @api
     */
    public function hasTemplate()
    {
        try {
            $this->baseRenderingContext->getTemplatePaths()->getTemplateSource(
                $this->baseRenderingContext->getControllerName(),
                $this->baseRenderingContext->getControllerAction()
            );

            return true;
        } catch (InvalidTemplateResourceException $e) {
            return false;
        }
    }
}
