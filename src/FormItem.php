<?php

namespace FormGenerator;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Error;
use ReflectionClass;

class FormItem
{
    private array $fieldOptions;
    private string $fieldContent;
    private FormGenerator $parent;
    private string $endTag;
    private \ReflectionProperty $property;

    public function __construct(FormGenerator $parent, \ReflectionProperty $property)
    {
        $this->parent = $parent;
        $this->fieldContent = "";
        $this->fieldOptions = array();
        $this->property = $property;
    }

    public function getName()
    {
        return $this->property->getName();
    }

    public function getFieldOptions(): array
    {
        return $this->fieldOptions;
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function generate(array $fieldOptions)
    {
        $rc = new ReflectionClass($this->parent->getObject());
        $annotationReader = new AnnotationReader();
        $this->endTag = "/>";
        $this->fieldContent = "";
        $this->fieldOptions = $fieldOptions;
        $type = $this->property->getType();

        if ($this->fieldOptions != [] && array_key_exists("type", $fieldOptions)) {
            if ($this->fieldOptions["type"] == "select") {
                $this->fieldContent .= "<select name='";
                $this->endTag = "</select>";
                $this->applyProperties($this->fieldContent, $this->fieldOptions, ["surround", "items", "optionLabel", "optionValue"]);
                if (!array_key_exists("items", $this->fieldOptions)) {
                    throw new \Exception("You must provide an array of items for your select field '" . $this->property->getName() . "'");
                }

                if (!array_key_exists("name", $this->fieldOptions)) {
                    $this->fieldContent .= "name='" . $this->parent->getClassName() . "-" . $this->property->getName() . "'>";
                }
                $optionsContent = null;
                if (is_array($this->fieldOptions["items"])) {
                    foreach ($this->fieldOptions["items"] as $option) {
                        $optionsContent .= "<option value='" . $option["value"] . "'>" . $option["name"] . "</option>";
                    }
                } else {
                    $optionsContent = $this->generateOptions($this->fieldOptions);
                }
                $this->fieldContent .= $optionsContent;
            } elseif ($this->fieldOptions["type"] == "textarea"){
                $this->fieldContent.= "<textarea ";
                $this->endTag = "</textarea>";
                $this->applyProperties($this->fieldContent, $this->fieldOptions, ["surround", "value"]);
                if (!array_key_exists("name", $this->fieldOptions)) {
                    $this->fieldContent .= "name='" . $this->parent->getClassName() . "-" . $this->property->getName() . "' ";
                }
                if (array_key_exists("value", $this->fieldOptions)) {
                    $this->fieldContent .= ">".$this->fieldOptions["value"];
                }else{
                    $this->fieldContent.= ">";
                }
            } else {
                $this->fieldContent .= "<input type='".$this->fieldOptions["type"]."'";
                $this->applyProperties($this->fieldContent, $this->fieldOptions, ["surround", "items"]);

                if (!array_key_exists("name", $this->fieldOptions)) {
                    $this->fieldContent .= "name='" . $this->parent->getClassName() . "-" . $this->property->getName() . "' ";
                }
            }
        } else {
            if ($type == null) {
                if ($this->parent->isDoctrineInstalled()) {
                    $annotation = array_filter($annotationReader->getPropertyAnnotations($rc->getProperty($this->property->getName())), function ($v) {
                        return $v instanceof Column;
                    });
                    $annotation = array_shift($annotation);
                    if ($annotation->type !== null) {
                        $type = $annotation->type;
                    } else {
                        throw new \Exception("You don't have typed '" . $this->property->getName() . "' of " . $this->parent->getClassName() . " class !");
                    }
                } else {
                    throw new \Exception("You don't have typed '" . $this->property->getName() . "' of " . $this->parent->getClassName() . " class !");
                }
            } else {
                $type = $type->getName();
            }

            switch ($type) {
                case "integer":
                case "int":
                    $this->fieldContent .= "<input type='number' ";
                    break;
                case "bool":
                    $this->fieldContent .= "<input type='checkbox' ";
                case "string":
                default:
                    $this->fieldContent .= "<input type='text' ";
                    break;
            }

            if ($this->fieldOptions == null || !array_key_exists("name", $this->fieldOptions)) {
                $this->fieldContent .= "name='" . $this->parent->getClassName() . "-" . $this->property->getName() . "' ";
            }

            if ($this->fieldOptions == null || !array_key_exists("placeholder", $this->fieldOptions)) {
                $this->fieldContent .= "placeholder='" . $this->property->getName() . "' ";
            }

            if ($this->fieldOptions == null || !array_key_exists("value", $this->fieldOptions)) {
                $getterMethodName = "get" . ucfirst($this->property->getName());
                if (method_exists($this->parent->getObject(), $getterMethodName)) {
                    try {
                        $value = $rc->getMethod($getterMethodName)->invoke($this->parent->getObject());
                        if (!is_object($value)) {
                            $this->fieldContent .= "value='$value' ";
                        }
                    } catch (Error $e) {

                    }
                } else {
                    throw new \Exception("The method " . $getterMethodName . " does not exist in your " . $this->parent->getClassName() . " class !");
                }
            }

            if ($fieldOptions != null) {
                $this->fieldContent = $this->applyProperties($this->fieldContent, $fieldOptions, ["surround"]);
            }
        }

        if ($fieldOptions != null && array_key_exists("surround", $fieldOptions)) {
            if (!strpos($fieldOptions["surround"], "{{content}}")) {
                throw new \Exception("Your " . $this->property->getName() . " surround option must contains '{{content}}' wich represents input content generated.");
            }
            $this->fieldContent = str_replace("{{content}}", $this->fieldContent . $this->endTag, $fieldOptions["surround"]);
        } else {
            $this->fieldContent = $this->fieldContent . $this->endTag;
        }
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
     * @throws \ReflectionException
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function generateOptions(array $fieldOptions)
    {
        $result = '';
        $annotationReader = new AnnotationReader();
        if (array_key_exists("optionLabel", $fieldOptions)) {
            if ($this->parent->isDoctrineInstalled()) {
                if (count($annotationReader->getClassAnnotations(new ReflectionClass($fieldOptions["items"]))) > 0) {
                    $entityManager = $this->parent->getEntityManager();
                    $entityRepository = $entityManager->getRepository($fieldOptions["items"]);
                    $entities = $entityRepository->findAll();
                    $optionLabel = $fieldOptions["optionLabel"];
                    if (array_key_exists("optionValue", $fieldOptions)) {
                        $optionValue = $fieldOptions["optionValue"];
                    } else {
                        $optionValue = $entityManager->getClassMetaData($fieldOptions["items"])->getSingleIdentifierFieldName();
                    }
                    foreach ($entities as $entity) {
                        $rc = new ReflectionClass($entity);
                        $value = $rc->getMethod("get" . ucfirst($optionValue))->invoke($entity);
                        $label = $rc->getMethod("get" . ucfirst($optionLabel))->invoke($entity);
                        $result .= "<option value='$value'>$label</option>";
                    }
                } else {
                    throw new \Exception("Your " . $fieldOptions["items"] . " class need to be annotated with Doctrine.");
                }
            } else {
                throw new \Exception("You must install Doctrine ORM for fetch a class.");
            }
        } else {
            throw new \Exception("You must provide the field name which will displayed.");
        }
        return $result;
    }

    /**
     * @throws \ReflectionException
     */
    public function addAttribute(string $attributeName, string $attributeValue)
    {
        if ($this->fieldOptions == null) $this->fieldOptions = array();
        $this->fieldOptions[$attributeName] = $attributeValue;
    }

    /**
     * @throws \Exception
     */
    public function updateAttribute(string $attributeName, string $attributeValue){
        if(array_key_exists($attributeName, $this->fieldOptions)){
            $this->fieldOptions[$attributeName] = $attributeValue;
        }else{
            throw new \Exception("The attribute $attributeName does not exists !");
        }
    }

    public function __toString()
    {
        return $this->fieldContent;
    }
}