<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\Sylius\TaxCategory;
use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\TaxationBundle\Form\Type\TaxCategoryChoiceType as BaseTaxCategoryChoiceType;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxation\Resolver\TaxRateResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductTaxCategoryChoiceType extends AbstractType
{
    private $taxCategoryRepository;
    private $translator;
    private $country;
    private $legacyTaxes = true;

    public function __construct(
        EntityRepository $taxCategoryRepository,
        TranslatorInterface $translator,
        TaxRateResolverInterface $taxRateResolver,
        ProductVariantFactoryInterface $productVariantFactory,
        string $country,
        bool $legacyTaxes)
    {
        $this->taxCategoryRepository = $taxCategoryRepository;
        $this->translator = $translator;
        $this->taxRateResolver = $taxRateResolver;
        $this->productVariantFactory = $productVariantFactory;
        $this->country = $country;
        $this->legacyTaxes = $legacyTaxes;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('choice_loader', function (Options $options) {

            return new ProductTaxCategoryChoiceLoader(
                $this->taxCategoryRepository,
                $this->taxRateResolver,
                $this->productVariantFactory,
                $this->country,
                $this->legacyTaxes
            );
        });

        $resolver->setDefault('choice_label', function (?TaxCategory $taxCategory) {

            $variant = $this->productVariantFactory->createNew();
            $variant->setTaxCategory($taxCategory);

            $rates = $this->taxRateResolver->resolveAll($variant);

            if (count($rates) === 0) {
                return '';
            }

            $amount = array_reduce(
                $rates->toArray(),
                fn($carry, $rate) => $carry + $rate->getAmount(),
                0.0
            );

            return sprintf('%s (%d%%)',
                $this->translator->trans($taxCategory->getName(), [], 'taxation'),
                $amount * 100
            );
        });

        $resolver->setDefault('placeholder', 'form.product_tax_category_choice.placeholder');
    }

    public function getParent(): string
    {
        return BaseTaxCategoryChoiceType::class;
    }
}
