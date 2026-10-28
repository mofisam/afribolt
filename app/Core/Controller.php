<?php
namespace App\Core;

abstract class Controller
{
    public function __construct(protected Request $request) {}

    protected function view(string $view, array $data = [], ?string $layout = 'public'): void
    {
        View::render($view, $data, $layout);
        Session::clearInput(); // old input/errors only live for one render
    }

    protected function redirect(string $to): never { Response::redirect($to); }
    protected function json(array $data, int $status = 200): never { Response::json($data, $status); }
    protected function abort(int $status, ?string $message = null): never { Response::abort($status, $message); }

    /** Validate POST input; on failure flash errors+input and go back. */
    protected function validate(array $rules, string $backTo): array
    {
        $v = Validator::make($this->request->all(), $rules);
        if ($v->fails()) {
            Session::flashInput($this->request->all(), $v->errors());
            Session::flash('error', 'Please fix the highlighted fields.');
            Response::redirect($backTo);
        }
        return $v->validated();
    }
}
