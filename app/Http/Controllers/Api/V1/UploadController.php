<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    private const ALLOWED_MIMES = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,mp3,mp4,wav';

    /** @var list<string> */
    private const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm', 'svg', 'cgi', 'pl',
    ];

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

        $file = $request->file('file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => ['This file type is not allowed.'],
            ]);
        }

        $directory = (string) $request->input('directory', 'uploads');
        $schoolId = $request->user()?->school_id;

        if (str_contains($directory, '..') || str_starts_with($directory, '/')) {
            return response()->json([
                'message' => 'Invalid upload directory.',
                'errors' => ['directory' => ['Directory path is not allowed.']],
            ], 422);
        }

        return $this->created(
            $this->uploads->store($file, $directory, 'public', $schoolId),
            'File uploaded'
        );
    }
}
