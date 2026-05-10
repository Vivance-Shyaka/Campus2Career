<?php
/**
 * Campus2Career - Email Service
 * Uses PHPMailer when installed, with a PHP mail() fallback for local demos.
 */
class EmailService {
    private string $from = 'noreply@campus2career.com';
    private string $fromName = 'Campus2Career';

    public function sendApproval(string $to, string $name, string $internshipTitle, string $companyName): bool {
        return $this->sendTemplate($to, 'Application approved - ' . $internshipTitle, [
            'accent' => '#10B981',
            'heading' => 'Application approved',
            'lead' => 'Congratulations, ' . $name . '.',
            'body' => [
                'Your application for ' . $internshipTitle . ' at ' . $companyName . ' has been approved.',
                'The company may contact you soon with the next step. Keep checking your dashboard and email.'
            ],
            'cta' => ['View application', $this->baseUrl() . 'views/student/applications.php']
        ]);
    }

    public function sendRejection(string $to, string $name, string $internshipTitle, string $companyName): bool {
        return $this->sendTemplate($to, 'Application update - ' . $internshipTitle, [
            'accent' => '#6366F1',
            'heading' => 'Application status update',
            'lead' => 'Hello ' . $name . ',',
            'body' => [
                'Thank you for applying for ' . $internshipTitle . ' at ' . $companyName . '.',
                'The company has decided not to move forward with your application at this time. Keep improving your profile and applying to roles that match your skills.'
            ],
            'cta' => ['Browse internships', $this->baseUrl() . 'views/student/internships.php']
        ]);
    }

    public function sendInterviewScheduled(
        string $to,
        string $name,
        string $internshipTitle,
        string $companyName,
        string $dateTime,
        string $location = '',
        string $notes = ''
    ): bool {
        $dt = new DateTime($dateTime);
        $details = [
            'Date' => $dt->format('l, F j, Y'),
            'Time' => $dt->format('g:i A')
        ];
        if ($location !== '') {
            $details['Location'] = $location;
        }
        if ($notes !== '') {
            $details['Notes'] = $notes;
        }

        return $this->sendTemplate($to, 'Interview scheduled - ' . $internshipTitle, [
            'accent' => '#3B82F6',
            'heading' => 'Interview scheduled',
            'lead' => 'Hello ' . $name . ',',
            'body' => [
                'An interview has been scheduled for your application for ' . $internshipTitle . ' at ' . $companyName . '.',
                'Please prepare well and confirm your availability with the company if requested.'
            ],
            'details' => $details,
            'cta' => ['View my applications', $this->baseUrl() . 'views/student/applications.php']
        ]);
    }

    public function sendNewApplication(string $to, string $companyName, string $studentName, string $internshipTitle): bool {
        return $this->sendTemplate($to, 'New application received - ' . $internshipTitle, [
            'accent' => '#8B5CF6',
            'heading' => 'New application received',
            'lead' => 'Hello ' . $companyName . ',',
            'body' => [
                $studentName . ' has submitted an application for ' . $internshipTitle . '.',
                'Open the applicants dashboard to review the student profile, skills, CV, certificates, and match score.'
            ],
            'cta' => ['Review applicants', $this->baseUrl() . 'views/company/applicants.php']
        ]);
    }

    private function sendTemplate(string $to, string $subject, array $payload): bool {
        $html = $this->template($subject, $payload);
        return $this->send($to, $subject, $html);
    }

    private function template(string $subject, array $payload): string {
        $accent = htmlspecialchars($payload['accent'] ?? '#3B82F6', ENT_QUOTES, 'UTF-8');
        $heading = htmlspecialchars($payload['heading'] ?? $subject, ENT_QUOTES, 'UTF-8');
        $lead = htmlspecialchars($payload['lead'] ?? '', ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        $paragraphs = '';
        foreach (($payload['body'] ?? []) as $line) {
            $paragraphs .= '<p style="margin:0 0 14px;color:#334155;line-height:1.7;">' .
                htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $detailsHtml = '';
        if (!empty($payload['details'])) {
            $detailsHtml .= '<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:16px;margin:18px 0;">';
            foreach ($payload['details'] as $label => $value) {
                $detailsHtml .= '<div style="margin:6px 0;color:#334155;"><strong>' .
                    htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ':</strong> ' .
                    nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) . '</div>';
            }
            $detailsHtml .= '</div>';
        }

        [$ctaText, $ctaUrl] = $payload['cta'] ?? ['Open Campus2Career', $this->baseUrl()];
        $ctaText = htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8');
        $ctaUrl = htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8');
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>{$safeSubject}</title></head>
<body style="margin:0;background:#EEF2F7;font-family:Segoe UI,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#EEF2F7;padding:32px 16px;">
    <tr><td align="center">
      <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#FFFFFF;border-radius:18px;overflow:hidden;box-shadow:0 18px 40px rgba(15,23,42,0.12);">
        <tr>
          <td style="background:linear-gradient(135deg,{$accent},#0F172A);padding:34px 38px;">
            <div style="color:#FFFFFF;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px;">Campus2Career</div>
            <h1 style="margin:0;color:#FFFFFF;font-size:26px;line-height:1.25;">{$heading}</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:34px 38px;">
            <p style="margin:0 0 18px;color:#0F172A;font-size:18px;font-weight:700;">{$lead}</p>
            {$paragraphs}
            {$detailsHtml}
            <div style="margin-top:26px;">
              <a href="{$ctaUrl}" style="display:inline-block;background:{$accent};color:#FFFFFF;text-decoration:none;padding:13px 22px;border-radius:10px;font-weight:700;">{$ctaText}</a>
            </div>
          </td>
        </tr>
        <tr>
          <td style="background:#F8FAFC;border-top:1px solid #E2E8F0;padding:18px 38px;color:#64748B;font-size:12px;text-align:center;">
            &copy; {$year} Campus2Career. Bridging education and employment.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    private function send(string $to, string $subject, string $html): bool {
        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithPHPMailer($to, $subject, $html);
        }

        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
            if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendWithPHPMailer($to, $subject, $html);
            }
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->from}>\r\n";
        $headers .= "Reply-To: {$this->from}\r\n";
        return @mail($to, $subject, $html, $headers);
    }

    private function sendWithPHPMailer(string $to, string $subject, string $html): bool {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: 'localhost';
            $mail->Port = (int)(getenv('SMTP_PORT') ?: 25);
            $mail->SMTPAuth = (bool)getenv('SMTP_USER');
            if ($mail->SMTPAuth) {
                $mail->Username = getenv('SMTP_USER');
                $mail->Password = getenv('SMTP_PASS') ?: '';
            }
            $secure = getenv('SMTP_SECURE') ?: '';
            if ($secure !== '') {
                $mail->SMTPSecure = $secure;
            }
            $mail->setFrom(getenv('SMTP_FROM') ?: $this->from, getenv('SMTP_FROM_NAME') ?: $this->fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));
            return $mail->send();
        } catch (Throwable $e) {
            return false;
        }
    }

    private function baseUrl(): string {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $proto . '://' . $host . '/campus2career/';
    }
}
