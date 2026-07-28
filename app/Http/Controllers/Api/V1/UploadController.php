<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\FileUploadService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    private const ALLOWED_MIMES = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,mp3,mp4,wav';

    public function __construct(private FileUploadService $uploads) {}

    /**
     * Generic authenticated-staff file upload used by resources, homework,
     * report cards, and profile avatars.
     */
    public function store(Request $request)
    {
        $this->authorizeModuleAccess($request, capabilities: ['isStaff']);

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:'.self::ALLOWED_MIMES],
            'directory' => ['sometimes', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\/\-]+$/'],
        ]);

        $directory = (string) $request->input('directory', 'uploads');
        $schoolId = $request->user()?->school_id;

        if (str_contains($directory, '..') || str_starts_with($directory, '/')) {
            return response()->json([
                'message' => 'Invalid upload directory.',
                'errors' => ['directory' => ['Directory path is not allowed.']],
            ], 422);
        }

        return $this->created(
            $this->uploads->store($request->file('file'), $directory, 'public', $schoolId),
            'File uploaded'
        );
    }
}
