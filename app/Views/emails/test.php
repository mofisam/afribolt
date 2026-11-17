<!--preheader:Email sending is working.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Test email</h1>
<p style="margin:0 0 16px">This is a test from your Afribolt platform. If you can read this, email sending works.</p>
<?= App\Core\View::partial('emails/_button', ['url' => url('/'), 'label' => 'Visit the website']) ?>
