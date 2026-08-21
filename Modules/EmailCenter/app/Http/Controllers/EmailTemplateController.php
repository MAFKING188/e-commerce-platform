<?php

namespace Modules\EmailCenter\Http\Controllers;

use Modules\EmailCenter\Models\EmailTemplate;
use Modules\EmailCenter\Http\Requests\StoreTemplateRequest;
use Modules\EmailCenter\Http\Requests\UpdateTemplateRequest;
use App\Http\Controllers\Controller;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::with('creator')->latest()->paginate(15);
        return view('emailcenter::admin.email-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('emailcenter::admin.email-templates.create');
    }

    public function store(StoreTemplateRequest $request)
    {
        EmailTemplate::create([
            'name' => $request->name,
            'subject' => $request->subject,
            'body_markdown' => $request->body_markdown,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.email-templates.index')
            ->with('status', 'Email template created.');
    }

    public function edit($id)
    {
        $template = EmailTemplate::findOrFail($id);
        return view('emailcenter::admin.email-templates.edit', compact('template'));
    }

    public function update(UpdateTemplateRequest $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $template->update([
            'name' => $request->name,
            'subject' => $request->subject,
            'body_markdown' => $request->body_markdown,
        ]);

        return redirect()->route('admin.email-templates.index')
            ->with('status', 'Email template updated.');
    }

    public function destroy($id)
    {
        $template = EmailTemplate::findOrFail($id);
        $template->delete();

        return redirect()->route('admin.email-templates.index')
            ->with('status', 'Email template deleted.');
    }
}