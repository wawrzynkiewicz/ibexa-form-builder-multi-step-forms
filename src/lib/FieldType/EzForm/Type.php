<?php

declare(strict_types=1);

namespace MateuszBieniek\EzPlatformFormBuilderMultiStepForms\FieldType\EzForm;

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\Value as BaseValue;
use Ibexa\FormBuilder\FieldType\Value as OriginalValue;
use Ibexa\Contracts\Core\FieldType\Value as ValueInterface;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue as PersistenceValue;
use Ibexa\FormBuilder\FieldType\Converter\FormConverter;
use Ibexa\FormBuilder\FieldType\FormFactory;
use Ibexa\Contracts\FormBuilder\FieldType\Model\Field;
use Ibexa\FormBuilder\FieldType\Type as DecoratedType;
use MateuszBieniek\EzPlatformFormBuilderMultiStepForms\Form\Type\StepFieldType;

class Type extends DecoratedType
{
    /** @var DecoratedType $ezFormType */
    private $decoratedType;

    /** @var \EzSystems\EzPlatformFormBuilder\FieldType\Converter\FormConverter */
    protected $converter;

    /** @var \EzSystems\EzPlatformFormBuilder\FieldType\FormFactory */
    private $formFactory;

    public function __construct(
        DecoratedType $decoratedType,
        FormConverter $converter,
        FormFactory $formFactory
    ) {
        $this->decoratedType = $decoratedType;
        $this->converter = $converter;
        $this->formFactory = $formFactory;
    }

    /**
     * @return string
     */
    public function getFieldTypeLabel(): string
    {
        return $this->decoratedType->getFieldTypeIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldName(ValueInterface $value, FieldDefinition $fieldDefinition, $languageCode)
    {
        return $this->decoratedType->getFieldName($value, $fieldDefinition, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    protected function createValueFromInput($inputValue)
    {
        if ($inputValue instanceof OriginalValue) {
            $inputValue = new Value($inputValue->getFormValue(), $inputValue->getForm());
        }
        if (is_string($inputValue)) {
            $inputValue = new Value($this->converter->decode($inputValue));
        }

        return $inputValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldTypeIdentifier()
    {
        return $this->decoratedType->getFieldTypeIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(ValueInterface $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return $this->decoratedType->getName($value, $fieldDefinition, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmptyValue()
    {
        return new Value($this->converter->fromArray([
            'fields' => [],
        ]));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \EzSystems\EzPlatformFormBuilder\Exception\ValidatorConstraintMapperNotFoundException
     */
    public function fromHash($hash)
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        $formValue = $this->converter->fromArray($hash);
        $form = $this->formFactory->createForm($formValue);

        $fields = $form->get('fields')->all();

        $isMultiStep = false;

        foreach ($fields as $field) {
            /** @var Field $fieldDefinition */
            $fieldDefinition = $field->getConfig()->getOption('field');
            if ($fieldDefinition->getIdentifier() === StepFieldType::STEP_FIELD_IDENTIFIER) {
                $isMultiStep = true;
                break;
            }
        }

        return new Value($formValue, $form, $isMultiStep);
    }

    /**
     * {@inheritdoc}
     */
    public function toPersistenceValue(ValueInterface $value)
    {
        return $this->decoratedType->toPersistenceValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromPersistenceValue(PersistenceValue $fieldValue)
    {
        if (empty($fieldValue->externalData)) {
            return $this->getEmptyValue();
        }

        return $this->fromHash($fieldValue->externalData);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkValueStructure(BaseValue $value)
    {
    }

    /**
     * @param \EzSystems\EzPlatformFormBuilder\FieldType\Value
     *
     * {@inheritdoc}
     */
    public function toHash(ValueInterface $value)
    {
        return $this->decoratedType->toHash($value);
    }

    /**
     * {@inheritdoc}
     */
    public function isSingular()
    {
        return $this->decoratedType->isSingular();
    }
}
