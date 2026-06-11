<?php

return [
    'created'           => 'Created successfully.',
    'creation_failed'   => 'Creation failed.',
    'updated'           => 'Updated successfully.',
    'update_failed'     => 'Update failed.',
    'deleted'           => 'Deleted successfully.',
    'deletion_failed'   => 'Deletion failed.',
    'not_found'         => 'Not found.',
    'empty'             => 'No data available.',

    'error_below_zero'  => 'The price for fixed type cannot be negative.',

    'book_unavailable'       => 'This book is currently unavailable for rental.',
    'rental_period_exceeded' => 'The maximum rental period is :days days.',
    'not_canceled'           => 'Only pending rentals can be canceled.',
    'canceled'               => 'Rental order canceled successfully.',
    'refund_initiated'       => 'Rental order canceled. Refund will be processed by the manager later.',

    'cannot_update_closed_rental' => 'You can not update closed rental.',
    'cannot_change_dates_for_paid' => 'You can not update date for paid rentals.',
    'cannot_change_payment_method_for_paid' => 'You can not update payment method for paid rental.',

    'cannot_delete_paid_rental'   => 'You cannot delete a paid rental.',
    'cannot_delete_active_rental' => 'You cannot delete an active rental.',
    'cannot_restore_no_copies'    => 'You cannot restore rental order because the book has no available copies.',
    'cannot_issue_rental_status'  => 'You cannot issue a rental order that is not in a pending status.',
    'cannot_issue_unpaid_rental'    => 'You cannot issue an unpaid rental order.',
    'cannot_return_inactive_rental' => 'You cannot return an inactive rental order.',
    'rental_issued'   => 'Rental order issued successfully.',
    'issue_failed'    => 'Failed to issue rental order.',
    'rental_returned'          => 'The book has been successfully returned to the library.',
    'rental_returned_with_fee' => 'The book has been returned. Attention: a penalty for overdue payment has been charged in the amount of :fee UAH.',
    'return_failed'   => 'Failed to return rental order.',


    'action_issue'  => 'Issue',
    'action_returned' => 'Return',
    'action_lost'   => 'Lost',
    'action_payment'=> 'Payment',
    'note_prefix'   => '[:date :action]: ', 

    'cannot_mark_lost_inactive'=> 'It is not possible to mark as lost: the order is not active.',
    'rental_marked_lost'       => 'The book has been marked as lost. A penalty for compensation has been charged in the amount of :fee UAH.',
    'lost_failed'              => 'It is not possible to mark the book as lost.',
    'user_has_unpaid_debts'    => 'Cannot create rental: you have unpaid late fees from previous rentals.',

    'unauthorized_action'          => 'Unauthorized action. You are not the owner of this rental.',
    'no_unpaid_debt'               => 'You have no unpaid late fees for this rental.',
    'debt_checkout_url_generated'  => 'Debt checkout URL generated successfully.',
    'debt_payment_failed'          => 'Failed to generate debt checkout URL.',

    'rental_already_paid'          => 'This rental is already fully paid.',
    'manual_payment_confirmed'     => 'Payment confirmed manually by manager.',
    'payment_confirmed_successfully' => 'Payment status successfully updated to Paid.',
    'payment_confirmation_failed'   => 'Failed to update payment status.',
];
