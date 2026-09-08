<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    private function decryptId(string $encrypted): int
    {
        $base64 = strtr($encrypted, '-_', '+/');
        $pad = 4 - (strlen($base64) % 4);
        if ($pad < 4) $base64 .= str_repeat('=', $pad);
        return (int) Crypt::decryptString($base64);
    }

    private function encryptId(int $id): string
    {
        return rtrim(strtr(Crypt::encryptString((string) $id), '+/', '-_'), '=');
    }

    private function canManageDocuments(): bool
    {
        $role = auth()->user()?->role;
        if (! $role) return false;
        if ($role->is_super) return true;
        return in_array('documents.manage', $role->permissions ?? []);
    }

    private function canViewLevel(?string $level = null): bool
    {
        $user = auth()->user();
        $level = $level ?? 'all_staff';

        if ($user && $user->role?->is_super) return true;
        if ($level === 'all_staff') return true;
        if (! $user) return false;
        if ($level === 'restricted') return $this->canManageDocuments();
        if ($level === 'admin_only') return in_array($user->role?->name, ['Super Administrator', 'Chairperson']);

        return false;
    }

    private function canViewDocument(Document $document): bool
    {
        return $this->canViewLevel($document->access_level);
    }

    public function index(Request $request)
    {
        $query = Document::with('category');

        if (! $this->canViewLevel('admin_only')) {
            $allowed = ['all_staff'];
            if ($this->canManageDocuments()) $allowed[] = 'restricted';
            $query->whereIn('access_level', $allowed);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('access')) {
            $query->where('access_level', $request->access);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('file_name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $documents = $query->latest()->paginate(15)->withQueryString();
        $categories = DocumentCategory::orderBy('name')->get();
        $totalDocs = Document::count();

        return view('documents.index', compact('documents', 'categories', 'totalDocs') + [
            'canManage' => $this->canManageDocuments(),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->canManageDocuments()) {
            abort(403, 'You do not have permission to upload documents.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:document_categories,id',
            'access_level' => 'required|in:all_staff,restricted,admin_only',
            'file' => 'required|file|max:2048',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        Document::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'category_id' => $request->category_id,
            'access_level' => $request->access_level,
            'uploaded_by' => auth()->user()->name ?? 'System',
            'user_id' => auth()->id(),
        ]);

        DocumentCategory::where('id', $request->category_id)->increment('documents_count');

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully');
    }

    public function download(string $encrypted)
    {
        $document = Document::findOrFail($this->decryptId($encrypted));
        if (! $this->canViewDocument($document)) {
            abort(403, 'You do not have access to this document.');
        }
        $path = storage_path('app/public/' . $document->file_path);
        if (!file_exists($path)) {
            abort(404, 'File not found');
        }
        return response()->download($path, $document->file_name);
    }

    public function preview(string $encrypted)
    {
        $document = Document::findOrFail($this->decryptId($encrypted));
        if (! $this->canViewDocument($document)) {
            abort(403, 'You do not have access to this document.');
        }
        $path = storage_path('app/public/' . $document->file_path);
        if (!file_exists($path)) {
            abort(404, 'File not found');
        }

        $mimeType = $document->file_type;
        $isImage = str_starts_with($mimeType, 'image/');
        $isPdf = $mimeType === 'application/pdf';
        $isText = str_starts_with($mimeType, 'text/');

        $content = null;
        if ($isText) {
            $content = file_get_contents($path);
        }

        $previewUrl = route('documents.preview.file', $encrypted);

        return view('documents.preview', compact('document', 'isImage', 'isPdf', 'isText', 'content', 'previewUrl'));
    }

    public function previewFile(string $encrypted)
    {
        $document = Document::findOrFail($this->decryptId($encrypted));
        if (! $this->canViewDocument($document)) {
            abort(403, 'You do not have access to this document.');
        }
        $path = storage_path('app/public/' . $document->file_path);
        if (!file_exists($path)) {
            abort(404, 'File not found');
        }

        return response()->file($path, [
            'Content-Type' => $document->file_type,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(string $encrypted)
    {
        if (! $this->canManageDocuments()) {
            abort(403, 'You do not have permission to delete documents.');
        }

        $document = Document::findOrFail($this->decryptId($encrypted));
        $categoryId = $document->category_id;

        $fullPath = storage_path('app/public/' . $document->file_path);
        if (file_exists($fullPath)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        DocumentCategory::where('id', $categoryId)->decrement('documents_count');

        return redirect()->route('documents.index')->with('success', 'Document deleted');
    }

    public function categories()
    {
        if (! $this->canManageDocuments()) {
            abort(403, 'You do not have permission to manage document categories.');
        }

        $categories = DocumentCategory::withCount('documents')->orderBy('name')->paginate(15);
        $totalCats = DocumentCategory::count();

        return view('documents.categories', [
            'categories' => $categories,
            'totalCats' => $totalCats,
            'canManage' => true,
        ]);
    }

    public function storeCategory(Request $request)
    {
        if (! $this->canManageDocuments()) {
            abort(403, 'You do not have permission to manage document categories.');
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:document_categories,name',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        DocumentCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?: '#2563EB',
        ]);

        return redirect()->route('documents.categories')->with('success', 'Category created');
    }

    public function updateCategory(Request $request, DocumentCategory $category)
    {
        if (! $this->canManageDocuments()) {
            abort(403, 'You do not have permission to manage document categories.');
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:document_categories,name,' . $category->id,
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?: $category->color,
        ]);

        return redirect()->route('documents.categories')->with('success', 'Category updated');
    }

    public function destroyCategory(DocumentCategory $category)
    {
        if (! $this->canManageDocuments()) {
            abort(403, 'You do not have permission to manage document categories.');
        }

        if ($category->documents_count > 0) {
            return redirect()->route('documents.categories')->with('error', 'Cannot delete category with existing documents. Move or delete them first.');
        }

        $category->delete();

        return redirect()->route('documents.categories')->with('success', 'Category deleted');
    }
}
