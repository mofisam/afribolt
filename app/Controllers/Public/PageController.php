<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\PlatformSetting;

class PageController extends Controller
{
    private const UPDATED = '29 October 2026';

    private function common(string $title): array
    {
        $draft = (bool) config('app.legal_draft', true);
        $email = '';
        try { $email = (string) PlatformSetting::value('support_email', ''); } catch (\Throwable $e) {}
        return [
            'title'   => $title,
            'updated' => self::UPDATED,
            'draft'   => $draft,
            'contact' => $email ?: (string) config('mail.from_address', 'support@example.com'),
            // Wrap terms the client must confirm. Highlighted while LEGAL_DRAFT=true, plain text once approved.
            'ph'      => fn(string $text) => $draft ? '<mark class="ph" title="Confirm with the business">' . e($text) . '</mark>' : e($text),
            // Internal reminders for the client: shown in draft mode only, never on the live site.
            'todo'    => fn(string $text) => $draft ? '<mark class="ph">TODO: ' . e($text) . '</mark>' : '',
        ];
    }

    public function privacy(): void { $this->view('public/pages/privacy', $this->common('Privacy policy')); }
    public function terms(): void { $this->view('public/pages/terms', $this->common('Terms of use')); }
    public function refund(): void { $this->view('public/pages/refund', $this->common('Refund & cancellation policy')); }

    public function faq(): void
    {
        $join = (int) config('security.join_early_minutes_default', 10);
        $faqs = [
            'Getting started' => [
                ['Do I need an account to look around?', 'No. You can browse courses, cohorts, dates and fees without signing in. You only need a free account when you are ready to register for a cohort.'],
                ['How do I register for a cohort?', 'Open the course, choose a cohort, and select <strong>Register</strong>. If the cohort needs payment, you will be taken to secure checkout straight after. Your seat is confirmed once the payment is verified.'],
                ['Which cohort should I pick?', 'Cohorts of the same course teach the same material and differ by start date and class times. Pick the one that fits your schedule. The cohort page shows every class date and the timezone.'],
            ],
            'Self-paced courses' => [
                ['How do self-paced courses work?', 'You can start straight away and learn whenever it suits you. Each course has sections with video, reading and quiz lessons. Your progress saves automatically, so you can stop and continue on any device.'],
                ['How long do I have access?', 'Lifetime access. Once you enrol, the course stays in <strong>My learning</strong> for as long as it exists on the platform.'],
                ['Do I get a certificate?', 'Yes, on courses that offer one. Finish every lesson (and pass any quizzes) and your certificate appears, with a link anyone can use to verify it.'],
                ['What is a free preview lesson?', 'Some lessons are marked <em>Preview</em> so you can try a course before you buy.'],
                ['I joined a live cohort. Can I use the course material?', 'Usually yes. Confirmed cohort students also get the on-demand lessons for that course, unless the course page says otherwise.'],
            ],
            'Jobs' => [
                ['Where do the jobs come from?', 'We collect openings from public sources such as feeds and job APIs, then use AI to tidy them up, sort them into categories and flag anything that looks like a scam. We always link you to the original posting.'],
                ['Does AfriBolt hire for these jobs?', 'No. Employers do the hiring. We only help you find and prepare for openings, and apply on the employer\'s own page.'],
                ['How are jobs recommended to me?', 'We match jobs to the interests and skills you give us and the topics of the courses you take. AI writes a short note on why a job may suit you. Your name and email are never sent to the AI provider.'],
                ['How do I avoid job scams?', 'Never pay to get a job, never share your BVN, card details or OTP, and be careful with WhatsApp-only offers. Use <strong>Report this job</strong> on any posting that looks wrong.'],
            ],
            'Classes & joining' => [
                ['How do the live classes work?', 'Classes run live online on Google Meet, Zoom or Microsoft Teams, depending on the programme. You join from your dashboard, so there are no links to hunt for in your inbox.'],
                ['Where is the Join Class button, and when does it work?', "It sits next to each class on your dashboard. It switches on {$join} minutes before the start and stays available until the class ends. Only confirmed students can use it."],
                ['What if a class is rescheduled or cancelled?', 'You will get a notification and an email, and your dashboard countdown and timetable update automatically. You do not need to do anything.'],
                ['What if I miss a class?', 'Tell your instructor as early as you can. Whether recordings are available depends on the programme. Check the course page or ask us before you register.'],
                ['What do I need to attend?', 'A laptop or phone, a stable internet connection, a headset is helpful, and the software named on the course page. Course requirements are listed under <strong>Requirements</strong> on each course page.'],
            ],
            'Cohorts, dates & seats' => [
                ['What timezone are class times shown in?', 'Each cohort page states its timezone (West Africa Time for most programmes). Dates and times are shown in that timezone so everyone sees the same thing.'],
                ['What if the cohort is full?', 'If the cohort has a waitlist you can join it. We will notify you if a seat opens, and you will then have a limited time to pay and confirm it.'],
                ['Can I register after the cohort has started?', 'Only when the cohort allows late registration. The cohort page tells you whether registration is open and until when.'],
                ['What does the countdown on my dashboard show?', 'Before the programme starts, the time left until day one. After it starts, the time left until your next class begins (or ends, if a class is in progress).'],
            ],
            'Payments' => [
                ['When is my seat confirmed?', 'As soon as your payment is verified with our payment provider. That usually takes seconds. Until then your seat is held for a short time and shows as <em>awaiting payment</em>.'],
                ['I paid but my dashboard still says awaiting payment.', 'Wait a minute and refresh. If it has not updated, <a href="' . url('/contact') . '">contact us</a> with your payment reference and enrolment number and we will check it with the provider. Please do not pay twice.'],
                ['Do you store my card details?', 'No. Payments are processed by our payment provider. We only receive confirmation that the payment succeeded, along with its reference.'],
                ['Can I pay in instalments?', 'Currently, fees are paid in full when you register. If that changes, it will be shown on the cohort page.'],
                ['What is your refund policy?', 'It depends on when you cancel. Read the full <a href="' . url('/refund-policy') . '">refund and cancellation policy</a>.'],
            ],
            'Career Accelerator' => [
                ['What is the Career Accelerator?', 'Career support for graduates and job seekers: CV and LinkedIn revamps, job search strategy, internship opportunities, interview coaching and reference-letter support. See the <a href="' . url('/career-accelerator') . '">Career Accelerator page</a>.'],
                ['Do you guarantee a job or internship?', 'No. We give you the preparation, tools and guidance to compete well, but hiring decisions belong to employers, so we cannot promise outcomes.'],
                ['How do I get started?', '<a href="' . url('/contact?type=career_consultation') . '">Book a free career consultation</a>. We will talk through your goals and suggest the right support.'],
            ],
            'Account & privacy' => [
                ['I forgot my password.', 'Use <a href="' . url('/forgot-password') . '">Forgot password</a>. We will email you a reset link that works for one hour.'],
                ['How do I change my email address or delete my data?', '<a href="' . url('/contact') . '">Contact us</a> from the email on your account. Read how we handle your data in the <a href="' . url('/privacy') . '">privacy policy</a>.'],
            ],
        ];
        $this->view('public/pages/faq', $this->common('Frequently asked questions') + ['faqs' => $faqs]);
    }
}
