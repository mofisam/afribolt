<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller};
use App\Services\JoinAccessService;

class SessionController extends Controller
{
    /** POST /student/sessions/{id}/join (AJAX). The meeting URL is only ever returned here. */
    public function join(string $id): void
    {
        $result = (new JoinAccessService())->urlFor((int) Auth::id('student'), (int) $id);
        $this->json($result, $result['ok'] ? 200 : 403);
    }
}
