<?php

namespace Isolate\Messenger;

use Isolate\Isolate;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class TransactionMiddleware implements MiddlewareInterface
{
    /**
     * @var Isolate
     */
    private $isolate;

    /**
     * @var string
     */
    private $contextName;

    /**
     * TransactionMiddleware constructor.
     * @param Isolate $isolate
     * @param string $contextName
     */
    public function __construct(Isolate $isolate, string $contextName = Isolate::DEFAULT_CONTEXT)
    {
        $this->isolate = $isolate;
        $this->contextName = $contextName;
    }

    /**
     * @param Envelope $envelope
     * @param StackInterface $stack
     * @return Envelope
     * @throws \Exception
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $context = $this->isolate->getContext($this->contextName);

        if($context->hasOpenTransaction()) {
            return $stack->next()->handle($envelope, $stack);
        }

        $transaction = $context->openTransaction();

        try {
            $returnValue = $stack->next()->handle($envelope, $stack);

            $context->closeTransaction();

            return $returnValue;
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }
}
