<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\FileUploadService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(private FileUploadService $uploads) {}

    /**
     * Generic authenticated-staff file upload used by resources, homework,
     * report cards, and profile avatars.
     */
    public function store(Request $request)
    {
        $this->authorizeModuleAccess($request, capabilities: ['isStaff']);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'directory' => ['sometimes', 'string', 'max:100'],
        ]);

        $directory = (string) $request->input('directory', 'uploads');
        $schoolId = $request->user()?->school_id;
        $scoped = $schoolId ? "school-{$schoolId}/{$directory}" : $directory;

        return $this->created(
            $this->uploads->store($request->file('file'), $scoped),
            'File uploaded'
        );
    }
}
