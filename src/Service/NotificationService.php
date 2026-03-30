<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private readonly InactivityService $inactivityService,
        private readonly SystemConfigService $systemConfigService,
        private readonly MailerInterface $mailer,
        private readonly Connection $connection,
    ) {
    }

    public function sendInactivityReport(): void
    {
        $recipients = $this->getRecipients();
        if (empty($recipients)) {
            return;
        }

        $users = $this->inactivityService->getAdminUsersWithStatus();
        $warning = array_filter($users, fn (array $u) => $u['status'] === 'warning');
        $danger = array_filter($users, fn (array $u) => $u['status'] === 'danger');
        $disabled = array_filter($users, fn (array $u) => $u['status'] === 'disabled');
        $neverLoggedIn = array_filter($users, fn (array $u) => $u['status'] === 'never_logged_in');

        if (empty($warning) && empty($danger) && empty($disabled) && empty($neverLoggedIn)) {
            return;
        }

        $html = $this->buildHtmlReport($warning, $danger, $disabled, $neverLoggedIn);
        $plain = $this->buildPlainReport($warning, $danger, $disabled, $neverLoggedIn);

        $senderEmail = $this->systemConfigService->getString('core.basicInformation.email') ?: 'noreply@localhost';

        $email = (new Email())
            ->from($senderEmail)
            ->subject('AdminGuard: Inactivity Report')
            ->html($html)
            ->text($plain);

        foreach ($recipients as $recipient) {
            $email->addTo($recipient);
        }

        $this->mailer->send($email);
    }

    private function getRecipients(): array
    {
        $configured = $this->systemConfigService->getString('ElixDigiAdminGuard.config.notificationRecipientEmail');
        if (!empty($configured)) {
            return array_map('trim', explode(',', $configured));
        }

        $emails = $this->connection->fetchFirstColumn(
            'SELECT email FROM `user` WHERE `admin` = 1 AND `active` = 1'
        );

        return $emails ?: [];
    }

    private function buildHtmlReport(array $warning, array $danger, array $disabled, array $neverLoggedIn): string
    {
        $html = '<h2>AdminGuard Inactivity Report</h2>';
        $html .= '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';

        if (!empty($danger)) {
            $html .= '<h3 style="color: #dc3545;">Danger (' . count($danger) . ')</h3>';
            $html .= $this->buildHtmlTable($danger);
        }

        if (!empty($warning)) {
            $html .= '<h3 style="color: #ffc107;">Warning (' . count($warning) . ')</h3>';
            $html .= $this->buildHtmlTable($warning);
        }

        if (!empty($neverLoggedIn)) {
            $html .= '<h3 style="color: #6c757d;">Never Logged In (' . count($neverLoggedIn) . ')</h3>';
            $html .= $this->buildHtmlTable($neverLoggedIn);
        }

        if (!empty($disabled)) {
            $html .= '<h3 style="color: #6c757d;">Disabled (' . count($disabled) . ')</h3>';
            $html .= $this->buildHtmlTable($disabled);
        }

        return $html;
    }

    private function buildHtmlTable(array $users): string
    {
        $html = '<table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">';
        $html .= '<tr style="background: #f8f9fa;">';
        $html .= '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Name</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Email</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Last Login</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Days Inactive</th>';
        $html .= '</tr>';

        foreach ($users as $user) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border: 1px solid #dee2e6;">' . htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #dee2e6;">' . htmlspecialchars($user['email']) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #dee2e6;">' . ($user['lastLoginAt'] ?? 'Never') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #dee2e6;">' . $user['daysInactive'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }

    private function buildPlainReport(array $warning, array $danger, array $disabled, array $neverLoggedIn): string
    {
        $text = "AdminGuard Inactivity Report\n";
        $text .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

        if (!empty($danger)) {
            $text .= "DANGER (" . count($danger) . "):\n";
            foreach ($danger as $user) {
                $text .= "  - {$user['firstName']} {$user['lastName']} ({$user['email']}) - {$user['daysInactive']} days inactive\n";
            }
            $text .= "\n";
        }

        if (!empty($warning)) {
            $text .= "WARNING (" . count($warning) . "):\n";
            foreach ($warning as $user) {
                $text .= "  - {$user['firstName']} {$user['lastName']} ({$user['email']}) - {$user['daysInactive']} days inactive\n";
            }
            $text .= "\n";
        }

        if (!empty($neverLoggedIn)) {
            $text .= "NEVER LOGGED IN (" . count($neverLoggedIn) . "):\n";
            foreach ($neverLoggedIn as $user) {
                $text .= "  - {$user['firstName']} {$user['lastName']} ({$user['email']}) - created {$user['createdAt']}\n";
            }
            $text .= "\n";
        }

        if (!empty($disabled)) {
            $text .= "DISABLED (" . count($disabled) . "):\n";
            foreach ($disabled as $user) {
                $text .= "  - {$user['firstName']} {$user['lastName']} ({$user['email']})\n";
            }
        }

        return $text;
    }
}
