<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\InvoiceStatus;
use PHPUnit\Framework\TestCase;

class InvoiceStatusTest extends TestCase
{
    public function test_draft_can_transition_to_sent_and_void(): void
    {
        $draft = InvoiceStatus::DRAFT;

        $this->assertTrue($draft->canTransitionTo(InvoiceStatus::SENT));
        $this->assertTrue($draft->canTransitionTo(InvoiceStatus::VOID));
        $this->assertFalse($draft->canTransitionTo(InvoiceStatus::PAID));
        $this->assertFalse($draft->canTransitionTo(InvoiceStatus::PARTIALLY_PAID));
        $this->assertFalse($draft->canTransitionTo(InvoiceStatus::OVERDUE));
        $this->assertFalse($draft->canTransitionTo(InvoiceStatus::DRAFT));
    }

    public function test_sent_can_transition_to_paid_partially_paid_overdue_and_void_if_unpaid(): void
    {
        $sent = InvoiceStatus::SENT;

        $this->assertTrue($sent->canTransitionTo(InvoiceStatus::PAID));
        $this->assertTrue($sent->canTransitionTo(InvoiceStatus::PARTIALLY_PAID));
        $this->assertTrue($sent->canTransitionTo(InvoiceStatus::OVERDUE));

        // Void is allowed only when amount_paid is 0
        $this->assertTrue($sent->canTransitionTo(InvoiceStatus::VOID, 0));
        $this->assertFalse($sent->canTransitionTo(InvoiceStatus::VOID, 5000));

        $this->assertFalse($sent->canTransitionTo(InvoiceStatus::DRAFT));
    }

    public function test_partially_paid_can_transition_to_paid_and_overdue_but_never_void(): void
    {
        $partiallyPaid = InvoiceStatus::PARTIALLY_PAID;

        $this->assertTrue($partiallyPaid->canTransitionTo(InvoiceStatus::PAID));
        $this->assertTrue($partiallyPaid->canTransitionTo(InvoiceStatus::OVERDUE));
        $this->assertFalse($partiallyPaid->canTransitionTo(InvoiceStatus::VOID));
        $this->assertFalse($partiallyPaid->canTransitionTo(InvoiceStatus::DRAFT));
    }

    public function test_overdue_can_transition_to_partially_paid_and_paid(): void
    {
        $overdue = InvoiceStatus::OVERDUE;

        $this->assertTrue($overdue->canTransitionTo(InvoiceStatus::PARTIALLY_PAID));
        $this->assertTrue($overdue->canTransitionTo(InvoiceStatus::PAID));
        $this->assertFalse($overdue->canTransitionTo(InvoiceStatus::DRAFT));
        $this->assertFalse($overdue->canTransitionTo(InvoiceStatus::VOID));
    }

    public function test_paid_and_void_are_terminal_states(): void
    {
        $paid = InvoiceStatus::PAID;
        $void = InvoiceStatus::VOID;

        foreach (InvoiceStatus::cases() as $status) {
            $this->assertFalse($paid->canTransitionTo($status), "Paid should not transition to {$status->value}");
            $this->assertFalse($void->canTransitionTo($status), "Void should not transition to {$status->value}");
        }
    }
}
