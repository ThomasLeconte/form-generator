<?php

namespace FormGenerator;

use Doctrine\ORM\EntityManager;
use ReflectionClass;

class FormGenerator
{
    private $entityManager;
    private bool $isDoctrineInstalled;
    private array $formItems;
    private string $className;
    private string $formResult;
    private array $fieldsOptions;
    private array $formOptions;
    private string $action;
    private $object;

    public function __construct($entityManager = null)
    {
        $this->formItems = array();
        $this->object = null;
        $this->isDoctrineInstalled = class_exists("Doctrine\ORM\EntityManager");
        if($this->isDoctrineInstalled){
            if ($entityManager !== null) {
                $this->entityManager = $entityManager;
            } else {
                $this->entityManager = include join(DIRECTORY_SEPARATOR, [__DIR__, '../bootstrap.php']);
            }
        }
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function isDoctrineInstalled()
    {
        return $this->isDoctrineInstalled;
    }

    private function getFormFieldByName($fieldName)
    {
        $formField = null;
        for ($i = 0; $i < count($this->formItems); $i++) {
            if ($this->formItems[$i]->getName() === $fieldName) {
                $formField = $this->formItems[$i];
            }
        }
        return $formField;
    }

    /**
     * @throws \Exception
     */
    public function addAttribute(string $fieldName, string $attributeName, string $attributeValue)
    {
        $formField = $this->getFormFieldByName($fieldName);
        if ($formField == null) throw new \Exception("Any form item found with '$fieldName' name !");
        $formField->addAttribute($attributeName, $attributeValue);
        $this->generate($this->object, $this->action, $this->fieldsOptions, $this->formOptions);
    }

    /**
     * @throws \Exception
     */
    public function updateAttribute(string $fieldName, string $attributeName, string $attributeValue){
        $formField = $this->getFormFieldByName($fieldName);
        if ($formField == null) throw new \Exception("Any form item found with '$fieldName' name !");
        $formField->updateAttribute($attributeName, $attributeValue);
        $this->generate($this->object, $this->action, $this->fieldsOptions, $this->formOptions);
    }

    private function applyProperties(&$element, $properties, $propertiesExceptions)
    {
        $properties = array_filter($properties, function ($v) use ($properties, $propertiesExceptions) {
            return !in_array(array_search($v, $properties), $propertiesExceptions);
        });
        foreach ($properties as $key => $value) {
            $element .= $key . "='$value' ";
        }
        return $element;
    }

    /**
     * @param object|string $object - Object to analyze
     * @param string $action Action of form
     * @param array $fieldsOptions Options for each of fields
     * @return $this
     * @throws \Exception
     */
    public function generate($object, string $action = '/', array $fieldsOptions = [], array $formOptions = [])
    {
        $this->object = $object;
        $this->action = $action;
        $this->fieldsOptions = $fieldsOptions;
        $this->formOptions = $formOptions;
        $this->formResult = "";

        if (is_string($this->object) || is_object($this->object)) {
            $rc = new ReflectionClass($this->object);
            if (count($rc->getProperties()) > 0) {
                if (is_string($this->object)) {
                    $this->object = $rc->newInstance();
                }
                $form = "<form method='POST' action='$action'" .
                    (count($formOptions) > 0 ? $this->applyProperties($form, $formOptions, ["surround", "action", "method"]) : "") .
                    ">";

                $inputs = $this->generateInputs($fieldsOptions, $rc);

                if ($formOptions != null && array_key_exists("surround", $formOptions)) {
                    if (!str_contains($formOptions["surround"], "{{content}}")) {
                        throw new \Exception("Your form surround option must contains '{{content}}' wich represents inputs content generated.");
                    }
                    $form .= str_replace("{{content}}", $inputs, $formOptions["surround"]);
                } else {
                    $form .= $inputs;
                }

                $form .= "<button type='submit'>Valider</button>";
                $form .= "</form>";
                $this->formResult = $form;
                return $this;
            } else {
                throw new \Exception("You can't generate a form of " . $rc->getName() . " because it doesn't have any property.");
            }
        } else {
            throw new \Exception("First argument must be an object or yourObject::class !");
        }
    }

    public function show()
    {
        echo $this->formResult;
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function generateInputs(array $fieldsOptions, $rc)
    {
        $result = '';
        $classNameExploded = explode("\\", get_class($this->object));
        $this->className = strtolower(end($classNameExploded));

        foreach ($rc->getProperties() as $property) {
            $fieldOptions = [];
            if (array_key_exists($property->getName(), $fieldsOptions)) {
                $fieldOptions = $fieldsOptions[$property->getName()];
            }

            $formItem = null;
            if ($this->getFormFieldByName($property->getName()) != null) {
                $formItem = $this->getFormFieldByName($property->getName());
                $formItem->generate($formItem->getFieldOptions());
            } else {
                $formItem = new FormItem($this, $property);
                $formItem->generate($fieldOptions);
                array_push($this->formItems, $formItem);
            }
        }
        for ($i = 0; $i < count($this->formItems); $i++) {
            $result .= $this->formItems[$i]->__toString();
        }
        return $result;
    }

    /**
     * @return EntityManager|mixed|null
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
}