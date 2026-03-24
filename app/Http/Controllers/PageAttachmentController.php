<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PageAttachmentController extends Controller
{
    /**
     * feat 1.9 — Upload a general file attachment to a page.
     */
    public function store(Request $request, Workspace $workspace, Page $page): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $page);

        $request->validate([
            'attachment' => [
                'required', 'file',
                'max:20480', // 20 MB
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,zip,csv',
            ],
        ]);

        $page->addMediaFromRequest('attachment')
            ->usingName($request->file('attachment')->getClientOriginalName())
            ->toMediaCollection('attachments');

        return back()->with('status', 'attachment-uploaded');
    }

    /**
     * feat 1.9 — Delete an attachment from a page.
     */
    public function destroy(Workspace $workspace, Page $page, Media $media): RedirectResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $page);
        abort_if($media->model_id !== $page->id, 404);

        $media->delete();

        return back()->with('status', 'attachment-deleted');
    }

    /**
     * feat 1.9 — TinyMCE image upload handler.
     *
     * TinyMCE POSTs the image as `file` (multipart/form-data) and expects:
     *   {"location": "https://…absolute-url-to-image…"}
     *
     * This endpoint stores the image in the `editor-images` collection and
     * returns the location so TinyMCE embeds it in the page content.
     */
    public function uploadEditorImage(Request $request, Workspace $workspace, Page $page): JsonResponse
    {
        abort_if($page->workspace_id !== $workspace->id, 404);
        $this->authorize('update', $page);

        $request->validate([
            // 'image' rule accepts JPEG, PNG, GIF, BMP, WebP, SVG — we further
            // restrict to raster formats only to prevent SVG-based XSS vectors.
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
        ]);

        $media = $page->addMediaFromRequest('file')
            ->usingName($request->file('file')->getClientOriginalName())
            ->toMediaCollection('editor-images');

        return response()->json(['location' => $media->getUrl()]);
    }
}
