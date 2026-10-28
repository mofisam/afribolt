<?php
namespace App\Controllers\Auth;

use App\Core\{Controller, Database, Session};

class VerifyEmailController extends Controller
{
    public function verify(string $token): void
    {
        $row = Database::one(
            'SELECT * FROM email_verification_tokens WHERE token_hash = ? AND verified_at IS NULL AND expires_at > UTC_TIMESTAMP() LIMIT 1',
            [hash('sha256', $token)]
        );
        if (!$row) {
            Session::flash('error', 'This verification link is invalid or has expired.');
            $this->redirect('/login');
        }
        Database::transaction(function () use ($row) {
            Database::execute('UPDATE email_verification_tokens SET verified_at = UTC_TIMESTAMP() WHERE id = ?', [(int) $row['id']]);
            Database::execute("UPDATE users SET email_verified_at = UTC_TIMESTAMP(), account_status = 'active' WHERE id = ? AND account_status = 'pending_verification'", [(int) $row['user_id']]);
        });
        Session::flash('success', 'Email verified. You can now register for cohorts.');
        $this->redirect('/login');
    }
}
