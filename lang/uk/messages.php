<?php

return [
    'created'           => 'Успішно створено.',
    'creation_failed'   => 'Створення не вдалося.',
    'updated'           => 'Успішно оновлено.',
    'update_failed'     => 'Оновлення не вдалося.',
    'deleted'           => 'Успішно видалено.',
    'deletion_failed'   => 'Видалення не вдалося.',
    'not_found'         => 'Не знайдено.',
    'empty'             => 'Немає доступних даних.',
    
    'error_below_zero'  => 'Ціна для фіксованого типу не може бути від\'ємною.',
    
    'book_unavailable'       => 'Ця книга тимчасово недоступна для оренди.',
    'rental_period_exceeded' => 'Максимальний період оренди це :days днів.',    
    'not_canceled'           => 'Тільки оренда в статусі "Очікує видачі" може бути скасована.',
    'canceled'               => 'Оренда успішно скасована.',
    'refund_initiated'       => 'Оренда скасована. Гроші будут повернені менеджером пізніше.',

    'cannot_update_closed_rental' => 'Ви не можете оновити завершену оренду.',
    'cannot_change_dates_for_paid'=> 'Ви не можете оновити дати оплаченої оренди.',
    'cannot_change_payment_method_for_paid' => 'Ви не можете змінити метод оплати для оплаченого замовлення.',

    'cannot_delete_paid_rental'   => 'Ви не можете видалити оплачену оренду.',
    'cannot_delete_active_rental' => 'Ви не можете видалити активну оренду.',
    'cannot_restore_no_copies'    => 'Ви не можете відновити оренду, тому що в книги немає доступних копій.',
    'cannot_issue_rental_status'  => 'Ви не можете видати цю оренду, оскільки вона не в статусі "Очікує видачі".',
    'cannot_issue_unpaid_rental'   => 'Ви не можете видати неоплачену оренду.',
    'cannot_return_inactive_rental' => 'Ви не можете повернути неактивну оренду.',
    'rental_issued'   => 'Оренду успішно видано.',
    'issue_failed'    => 'Не вдалося видати оренду.',
    'rental_returned'          => 'Книгу успішно повернено до бібліотеки.',
    'rental_returned_with_fee' => 'Книгу повернено. Увага: нараховано штраф за протермінування у розмірі :fee грн.',
    'return_failed'   => 'Не вдалося повернути оренду.',


    'action_issue'  => 'Issue',
    'action_return' => 'Return',
    'note_prefix'   => '[:date :action]: ', 
];
