<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class DocumentModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Administrator']);
        $user = User::factory()->create(['name' => 'Big Leader', 'role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    private function committeeUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Committee Member']);
        $user = User::factory()->create(['name' => 'Committee Person', 'role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    private function encId(int $id): string
    {
        return rtrim(strtr(Crypt::encryptString((string) $id), '+/', '-_'), '=');
    }

    private function makeDoc(string $title, string $access): Document
    {
        $cat = DocumentCategory::firstOrCreate(['name' => 'Policies', 'slug' => 'policies']);

        return Document::create([
            'title' => $title,
            'file_path' => 'documents/'.$title.'.pdf',
            'file_name' => $title.'.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
            'category_id' => $cat->id,
            'access_level' => $access,
            'uploaded_by' => 'Test',
        ]);
    }

    public function test_documents_page_renders_for_admin(): void
    {
        $this->adminUser();
        $this->makeDoc('Admin Doc', 'all_staff');

        $resp = $this->get(route('documents.index'));

        $resp->assertOk();
        $resp->assertSee('Document Center');
        $resp->assertSee('Upload Document');
        $resp->assertSee('docDetailDrawer');
    }

    public function test_non_manager_sees_only_all_staff_documents(): void
    {
        $this->committeeUser();
        $this->makeDoc('Confidential File', 'admin_only');
        $this->makeDoc('Public Handout', 'all_staff');

        $resp = $this->get(route('documents.index'));

        $resp->assertOk();
        $resp->assertSee('Public Handout');
        $resp->assertDontSee('Confidential File');
    }

    public function test_upload_requires_manage_permission(): void
    {
        $this->committeeUser();
        $cat = DocumentCategory::firstOrCreate(['name' => 'Policies', 'slug' => 'policies']);

        $resp = $this->post(route('documents.store'), [
            'title' => 'Sneaky',
            'category_id' => $cat->id,
            'access_level' => 'all_staff',
        ]);

        $resp->assertForbidden();
    }

    public function test_categories_requires_manage_permission(): void
    {
        $this->committeeUser();
        $this->get(route('documents.categories'))->assertForbidden();

        $this->adminUser();
        $this->get(route('documents.categories'))->assertOk()->assertSee('Add Category');
    }

    public function test_download_denied_for_non_manager(): void
    {
        $this->committeeUser();
        $doc = $this->makeDoc('Secret File', 'admin_only');

        $this->get(route('documents.download', $this->encId($doc->id)))->assertForbidden();
    }

    public function test_sidebar_lists_documents(): void
    {
        $this->adminUser();

        $resp = $this->get(route('documents.index'));

        $resp->assertSee('/documents', false);
        $resp->assertSee('/documents/categories', false);
    }
}