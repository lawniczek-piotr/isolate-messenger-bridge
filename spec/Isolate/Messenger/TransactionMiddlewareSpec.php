<?php

namespace spec\Isolate\Messenger;

use Isolate\PersistenceContext;
use Isolate\Tests\DummyMessage;
use Isolate\Isolate;
use Isolate\PersistenceContext\Factory;
use Isolate\PersistenceContext\Transaction;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class TransactionMiddlewareSpec extends ObjectBehavior
{
    function it_is_middleware(Factory $contextFactory)
    {
        $isolate = new Isolate($contextFactory->getWrappedObject());
        $this->beConstructedWith($isolate, $this->contextName());

        $this->shouldImplement(MiddlewareInterface::class);
    }

    function it_opens_and_closes_transaction_for_context_for_next_middleware_if_doesnt_exist_yet(Factory $contextFactory, PersistenceContext $context, MiddlewareInterface $middleware, StackInterface $stack)
    {
        $envelope = $this->dummyEnvelope();
        
        $stack->beADoubleOf(StackInterface::class);
        $middleware->beADoubleOf(MiddlewareInterface::class);
        $middleware->handle($envelope, $stack)->willReturn($envelope);
        $stack->next()->willReturn($middleware);

        $contextFactory->beADoubleOf(Factory::class);
        $context->beADoubleOf(PersistenceContext::class);
        $contextFactory->create($this->contextName())->willReturn($context);

        $isolate = new Isolate($contextFactory->getWrappedObject());
        $this->beConstructedWith($isolate, $this->contextName());

        $context->hasOpenTransaction()->shouldBeCalled();
        $context->openTransaction()->shouldBeCalled();
        $context->closeTransaction()->shouldBeCalled();

        $this->handle($this->dummyEnvelope(), $stack);
    }

    function it_opens_and_rollbacks_transaction_for_context_for_next_middleware_if_doesnt_exist_yet_and_exception_was_thrown(Factory $contextFactory, PersistenceContext $context, Transaction $transaction, MiddlewareInterface $middleware, StackInterface $stack)
    {
        $envelope = $this->dummyEnvelope();
        
        $stack->beADoubleOf(StackInterface::class);
        $middleware->beADoubleOf(MiddlewareInterface::class);
        $middleware->handle($envelope, $stack)->willThrow(new \Exception());
        $stack->next()->willReturn($middleware);

        $transaction->beADoubleOf(Transaction::class);
        $transaction->rollback()->shouldBeCalled();
        $context->beADoubleOf(PersistenceContext::class);
        $context->beConstructedWith([$transaction->getWrappedObject()]);
        $context->openTransaction()->willReturn($transaction);
        $context->hasOpenTransaction()->willReturn(false);
        $contextFactory->create($this->contextName())->willReturn($context);

        $isolate = new Isolate($contextFactory->getWrappedObject());
        $this->beConstructedWith($isolate, $this->contextName());

        $context->hasOpenTransaction()->shouldBeCalled();
        $context->openTransaction()->shouldBeCalled();
        $context->closeTransaction()->shouldNotBeCalled();

        $this->shouldThrow(\Exception::class)->duringHandle($this->dummyEnvelope(), $stack);
    }

    function it_doesnt_open_transaction_for_context_if_already_opened(Factory $contextFactory, PersistenceContext $context, MiddlewareInterface $middleware, StackInterface $stack)
    {
        $envelope = $this->dummyEnvelope();
        
        $stack->beADoubleOf(StackInterface::class);
        $middleware->beADoubleOf(MiddlewareInterface::class);
        $middleware->handle($envelope, $stack)->willReturn($envelope);
        $stack->next()->willReturn($middleware);

        $context->beADoubleOf(PersistenceContext::class);
        $context->hasOpenTransaction()->willReturn(true);
        $contextFactory->create($this->contextName())->willReturn($context);

        $isolate = new Isolate($contextFactory->getWrappedObject());
        $this->beConstructedWith($isolate, $this->contextName());

        $context->hasOpenTransaction()->shouldBeCalled();
        $context->openTransaction()->shouldNotBeCalled();
        $context->closeTransaction()->shouldNotBeCalled();

        $this->handle($this->dummyEnvelope(), $stack);
    }

    private function contextName(): string
    {
        return new PersistenceContext\Name(Isolate::DEFAULT_CONTEXT);
    }

    private function dummyEnvelope(): Envelope
    {
        $message = new DummyMessage('Whatever');
        $envelope = new Envelope($message);
        return $envelope;
    }
}
