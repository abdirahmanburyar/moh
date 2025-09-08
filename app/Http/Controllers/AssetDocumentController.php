<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AssetDocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = AssetDocument::with(['asset'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('file_name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('asset_id'), function ($query) use ($request) {
                $query->where('asset_id', $request->asset_id);
            })
            ->when($request->filled('document_type'), function ($query) use ($request) {
                $query->where('document_type', $request->document_type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('AssetDocuments/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'asset_id', 'document_type']),
            'assets' => Asset::select('id', 'asset_number')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('AssetDocuments/Create', [
            'assets' => Asset::select('id', 'asset_number')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'document_type' => 'required|string|max:255',
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:1000',
        ]);

        $asset = Asset::findOrFail($request->asset_id);
        
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("assets/{$asset->id}/documents", $fileName, 'public');
            
            $document = $asset->documents()->create([
                'document_type' => $request->document_type,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $request->description,
                'meta_data' => [
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now()->toISOString(),
                ],
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully.',
                    'document' => $document
                ]);
            }

            return redirect()->route('asset.documents.index')
                ->with('success', 'Document uploaded successfully.');
        }

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No file was uploaded.',
                'errors' => ['file' => ['No file was uploaded.']]
            ], 422);
        }

        return back()->withErrors(['file' => 'No file was uploaded.']);
    }

    public function show(AssetDocument $document)
    {
        $document->load('asset');
        
        return Inertia::render('AssetDocuments/Show', [
            'document' => $document,
        ]);
    }

    public function edit(AssetDocument $document)
    {
        $document->load('asset');
        
        return Inertia::render('AssetDocuments/Edit', [
            'document' => $document,
            'assets' => Asset::select('id', 'asset_number')->get(),
        ]);
    }

    public function update(Request $request, AssetDocument $document)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'document_type' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        $document->update([
            'asset_id' => $request->asset_id,
            'document_type' => $request->document_type,
            'description' => $request->description,
        ]);

        // Handle file replacement if new file is uploaded
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            
            // Delete old file
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
            
            // Store new file
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("assets/{$request->asset_id}/documents", $fileName, 'public');
            
            $document->update([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        return redirect()->route('asset.documents.index')
            ->with('success', 'Document updated successfully.');
    }

    public function destroy(Request $request, AssetDocument $document)
    {
        // Delete physical file
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        
        $document->delete();

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.'
            ]);
        }

        return redirect()->route('asset.documents.index')
            ->with('success', 'Document deleted successfully.');
    }

    public function download(AssetDocument $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name
        );
    }

    public function preview(AssetDocument $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        // Serve the file content for preview (browser will handle display)
        return response()->file(Storage::disk('public')->path($document->file_path));
    }
}
