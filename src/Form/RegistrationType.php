<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationType extends AbstractType
{
    private $urlGenerator;
    private $isDemo;

    public function __construct(UrlGeneratorInterface $urlGenerator, bool $isDemo = false)
    {
        $this->urlGenerator = $urlGenerator;
        $this->isDemo = $isDemo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('givenName', TextType::class, array('label' => 'profile.givenName'))
            ->add('familyName', TextType::class, array('label' => 'profile.familyName'))
            // Phone number will be asked during checkout
            // @see AppBundle\Form\Checkout\CheckoutAddressType
            ->add('legal', CheckboxType::class, array(
                'mapped' => false,
                'required' => true,
                'label' => 'form.registration.legal.label',
                'help' => 'form.registration.legal.help',
                'help_translation_parameters' => [
                    '%terms_url%' => $this->urlGenerator->generate('terms', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    '%privacy_url%' => $this->urlGenerator->generate('privacy', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
                'help_html' => true,
            ))
            ;

        if ($this->isDemo) {
            $builder->add('accountType', ChoiceType::class, [
                'mapped' => false,
                'required' => true,
                'choices'  => [
                    'roles.ROLE_USER' => 'CUSTOMER',
                    'roles.ROLE_COURIER' => 'COURIER',
                    'roles.ROLE_RESTAURANT' => 'RESTAURANT',
                    'roles.ROLE_STORE' => 'STORE',
                ],
                'label' => 'profile.accountType'
            ]);
        }

        // Add help to "username" field
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $child = $form->get('username');
            $config = $child->getConfig();
            $options = $config->getOptions();
            $options['help'] = 'form.registration.username.help';
            $form->add('username', get_class($config->getType()->getInnerType()), $options);
        });
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }
}
