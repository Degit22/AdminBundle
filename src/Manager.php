<?php

namespace Creonit\AdminBundle;

use Creonit\AdminBundle\Component\Field\CheckboxField;
use Creonit\AdminBundle\Component\Field\DateField;
use Creonit\AdminBundle\Component\Field\ExternalField;
use Creonit\AdminBundle\Component\Field\Field;
use Creonit\AdminBundle\Component\Field\FileField;
use Creonit\AdminBundle\Component\Field\GalleryField;
use Creonit\AdminBundle\Component\Field\ImageField;
use Creonit\AdminBundle\Component\Field\SelectField;
use Creonit\AdminBundle\Component\Field\VideoField;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\AdminBundle\Exception\RequestException;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Manager {

    protected $title;
    protected $icon;

    /** @var  Plugin[] */
    protected $plugins;

    /** @var  Module[] */
    protected $modules = [];

    /** @var  ContainerInterface */
    protected $container;

    /** @var  TwigEngine */
    protected $templating;

    /** @var  Module */
    protected $activeModule;

    protected $fieldTypes = [];
    protected $fieldHelpers = [];

    public function __construct(ContainerInterface $container, TwigEngine $templating)
    {
        $this->container = $container;
        $this->templating = $templating;
    }


    public function configure(){

        $this->addFieldTypes([
            Field::class,
            CheckboxField::class,
            DateField::class,
            ExternalField::class,
            FileField::class,
            ImageField::class,
            VideoField::class,
            GalleryField::class,
            SelectField::class,
        ]);

    }

    public function initialize(){
        $this->configure();

        foreach ($this->plugins as $plugin){
            $this->addFieldTypes($plugin->getFieldTypes());
        }
    }
    
    public function addFieldType($className){
        $this->fieldTypes[$className::TYPE] = $className;
    }

    public function addFieldTypes(array $classNames){
        foreach($classNames as $className){
            $this->addFieldType($className);
        }
    }

    public function hasModule($moduleName){
        return array_key_exists($moduleName, $this->modules);
    }

    public function getModule($moduleName){
        return $this->modules[$moduleName];
    }

    public function addModule(Module $module){
        $this->modules[$module->getName()] = $module;
        $module->setManager($this);
        $module->setContainer($this->container);
    }

    public function addPlugin(Plugin $plugin){
        $plugin->configure();
        $this->plugins[] = $plugin;
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @return Module
     */
    public function getActiveModule(){
        return $this->activeModule;
    }

    /**
     * @param Module $module
     * @return $this
     */
    public function setActiveModule(Module $module){
        $this->activeModule = $module;
        $module->setActive(true);
        return $this;
    }

    /**
     * @return Module[]
     */
    public function getModules(){
        return $this->modules;
    }

    public function handleRequest(Request $request){
        $componentRequests = array_merge_recursive($request->request->all(), $request->files->all())['request'];
        $componentResponses = [];

        if(!$componentRequests || !is_array($componentRequests)) {
            throw new RequestException('Bad request');
        }

        foreach ($componentRequests as $id => $componentRequest) {
            try {
                if(empty($componentRequest['name']) || !preg_match('/^(\w+)\.(\w+)$/', $componentRequest['name'], $componentMatch)){
                    throw new RequestException(sprintf('Wrong component name format "%s"', isset($componentRequest['name']) ? $componentRequest['name'] : ''));
                }

                if(!$this->hasModule($componentMatch[1])){
                    throw new RequestException(sprintf('Module "%s" not found', $componentMatch[1]));
                }

                $module = $this->getModule($componentMatch[1]);
                $module->initialize();

                if(!$module->hasComponent($componentMatch[2])){
                    throw new RequestException(sprintf('Component "%s" in module "%s" not found', $componentMatch[2], $componentMatch[1]));
                }

                $component = $module->getComponent($componentMatch[2]);
                $component->initialize();
                $componentResponses[] = $component->handleRequest(new ComponentRequest($componentRequest))->dump();

            }catch(RequestException $e){
                $componentResponse = new ComponentResponse();
                $componentResponse->error($e->getMessage());
                $componentResponses[] = $componentResponse->dump();
            }catch(\Exception $e){
                $componentResponse = new ComponentResponse();
                //$componentResponse->error('Произошла системная ошибка');
                $componentResponse->error($e->getMessage());
                $componentResponses[] = $componentResponse->dump();
            }
        }

        return new JsonResponse($componentResponses);

    }

    /**
     * @param mixed $title
     * @return Manager
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $icon
     * @return Manager
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    public function createField($name, $parameters, $type = null)
    {
        /** @var Field $field */
        $field = new $this->fieldTypes[$type ?: 'default'];
        $field->setContainer($this->container);
        $field->setName($name);
        $field->parameters->add($parameters);
        return $field;
    }



} 