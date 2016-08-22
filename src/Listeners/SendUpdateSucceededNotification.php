<?php

namespace Codedge\Updater\Listeners;

use Codedge\Updater\Events\UpdateSucceeded;
use Illuminate\Mail\Mailer;
use Illuminate\Log\Writer;

/**
 * SendUpdateSucceededNotification.php.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
class SendUpdateSucceededNotification
{
    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * SendUpdateAvailableNotification constructor.
     *
     * @param Writer $logger
     * @param Mailer $mailer
     */
    public function __construct(Writer $logger, Mailer $mailer)
    {
        $this->logger = $logger->getMonolog();
        $this->mailer = $mailer;
    }

    /**
     * Handle the event.
     *
     * @param UpdateSucceeded $event
     */
    public function handle(UpdateSucceeded $event)
    {
        if (config('self-update.log_events')) {
            $this->logger->addInfo('['.$event->getEventName().'] event: Notification triggered.');
        }

        $sendToAddress = config('self-update.mail_to.address');
        $sendToName = config('self-update.mail_to.name');
        $subject = config('self-update.mail_to.subject_update_succeeded');

        if (empty($sendToAddress)) {
            $this->logger->addCritical(
                '['.$event->getEventName().'] event: '
                .'Missing recipient email address. Please set SELF_UPDATER_MAILTO_ADDRESS in your .env file.'
            );
        }

        if (empty($sendToName)) {
            $this->logger->addWarning(
                '['.$event->getEventName().'] event: '
                .'Missing recipient email name. Please set SELF_UPDATER_MAILTO_NAME in your .env file.'
            );
        }

        $this->mailer->send(
            'vendor.self-update.mails.update-available',
            [
                'newVersion' => $event->getVersionAvailable(),
            ],
            function ($m) use ($subject, $sendToAddress, $sendToName) {
                $m->subject($subject);
                $m->from(config('mail.from.address'), config('mail.from.name'));
                $m->to($sendToAddress, $sendToName);
            }
        );
    }
}
