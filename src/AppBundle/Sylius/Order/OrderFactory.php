<?php

namespace AppBundle\Sylius\Order;

use AppBundle\Entity\Delivery;
use AppBundle\Entity\Restaurant;
use AppBundle\Entity\Task;
use AppBundle\Service\SettingsManager;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxation\Calculator\CalculatorInterface;

class OrderFactory implements FactoryInterface
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ChannelContextInterface
     */
    private $channelContext;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory, ChannelContextInterface $channelContext)
    {
        $this->factory = $factory;
        $this->channelContext = $channelContext;
    }

    /**
     * {@inheritdoc}
     */
    public function createNew()
    {
        $order = $this->factory->createNew();
        $order->setChannel($this->channelContext->getChannel());

        return $order;
    }

    public function createForRestaurant(Restaurant $restaurant): OrderInterface
    {
        $order = $this->createNew();
        $order->setRestaurant($restaurant);

        return $order;
    }

    public function createForDelivery(Delivery $delivery): OrderInterface
    {
        foreach ($delivery->getTasks() as $task) {
            $task->setStatus(Task::STATUS_VIRTUAL);
        }

        $order = $this->createNew();
        $order->setDelivery($delivery);

        return $order;
    }
}
