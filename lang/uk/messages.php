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
    'cannot_change_dates_for_paid' => 'Ви не можете оновити дати оплаченої оренди.',
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


    'action_issue'  => 'Видача',
    'action_returned' => 'Повернення',
    'action_lost'   => 'Втрата',
    'action_payment' => 'Оплата',
    'action_cancel' => 'Скасування',
    'action_refunded' => 'Повернення коштів',
    'note_prefix'   => '[:date :action]: ',

    'cannot_mark_lost_inactive' => 'Неможливо відмітити як втрачену: замовлення не є активним.',
    'rental_marked_lost'       => 'Книгу відмічено як втрачену. Нараховано штраф для відшкодування: :fee грн.',
    'lost_failed'              => 'Не вдалося відмітити книгу як втрачену.',
    'user_has_unpaid_debts'    => 'Неможливо оформити замовлення: у вас є неоплачені штрафи за попередні оренди.',

    'unauthorized_action'          => 'Доступ заборонено. Ви не є власником цього замовлення.',
    'no_unpaid_debt'               => 'У вас немає неоплачених штрафів за цим замовленням.',
    'debt_checkout_url_generated'  => 'Посилання на оплату боргу успішно згенеровано.',
    'debt_payment_failed'          => 'Не вдалося згенерувати посилання на оплату штрафу.',

    'rental_already_paid'          => 'Це замовлення вже повністю оплачене.',
    'manual_payment_confirmed'     => 'Оплату підтверджено менеджером вручну.',
    'payment_confirmed_successfully' => 'Статус оплати успішно оновлено на "Оплачено".',
    'payment_confirmation_failed'   => 'Не вдалося оновити статус оплати.',

    'email_cancelled_subject'  => 'Скасування замовлення №:id',
    'email_cancelled_greeting' => 'Вітаємо, :name!',
    'email_cancelled_body'     => 'Повідомляємо, що ваше замовлення на книгу «:title» було скасовано.',
    'email_cancelled_reason'   => 'Причина: :reason',
    'email_footer'             => 'Дякуємо, що користуєтесь нашим сервісом!',
    'auto_cancel_reason'       => 'Система: Автоматичне скасування (не забрано протягом :days днів).',

    'email_overdue_subject'  => 'Протермінування оренди замовлення №:id',
    'email_overdue_greeting' => 'Увага, :name!',
    'email_overdue_body'     => 'Повідомляємо, що термін оренди книги «:title» закінчився :date.',
    'email_overdue_warning'  => 'Будь ласка, поверніть книгу найближчим часом, щоб уникнути додаткових штрафів за кожен день запізнення.',

    'cannot_refund_unpaid'      => 'Не неможливо повернути кошти, оскільки замовлення не було оплачено.',
    'manual_refund_confirmed'   => 'Кошти успішно повернуто клієнту.',
    'refund_successful'         => 'Статус повернення коштів успішно оновлено.',

    'email_welcome_subject'      => 'Ваш акаунт успішно створено!',
    'email_welcome_greeting'     => 'Вітаємо, :name!',
    'email_account_created'      => 'Адміністратор створив для вас особистий кабінет у нашій системі.',
    'email_your_credentials'     => 'Ваші дані для входу:',
    'email_login_button'         => 'Увійти в кабінет',
    'email_change_password_note' => 'З міркувань безпеки рекомендуємо змінити цей пароль у налаштуваннях профілю після першого входу.',
    'user_created_successfully'  => 'Користувача успішно створено.',

    'cannot_block_self'                => 'Ви не можете заблокувати власний акаунт.',
    'cannot_change_own_role'           => 'Ви не можете змінити власну роль.',
    'user_blocked_successfully'        => 'Користувача успішно заблоковано.',
    'user_unblocked_successfully'      => 'Користувача успішно розблоковано.',
    'user_role_changed_successfully'   => 'Роль користувача успішно змінено.',
];
