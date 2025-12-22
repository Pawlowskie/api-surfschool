<?php

namespace App\EventSubscriber;

use App\Exception\AvailableSpotsNotInitializedException;
use App\Exception\BookingSessionRequiredException;
use App\Exception\CapacityBelowBookedSeatsException;
use App\Exception\CapacityExceededException;
use App\Exception\InvalidCapacityException;
use App\Exception\NoAvailableSpotsException;
use App\Exception\SessionCancelledException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 20],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $status = match (true) {
            $exception instanceof NoAvailableSpotsException,
            $exception instanceof SessionCancelledException,
            $exception instanceof CapacityExceededException => 409,
            $exception instanceof BookingSessionRequiredException,
            $exception instanceof AvailableSpotsNotInitializedException,
            $exception instanceof InvalidCapacityException,
            $exception instanceof CapacityBelowBookedSeatsException => 400,
            default => null,
        };

        if ($status === null) {
            return;
        }

        $event->setThrowable(new HttpException($status, $exception->getMessage(), $exception));
    }
}
