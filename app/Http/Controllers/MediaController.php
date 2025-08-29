<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class MediaController extends BaseController
{
    public function store(Request $request)
    {
        Log::info('file : ', ['file' => $request->all()]);
        $validatedData = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png|max:10240', // max 10MB
        ]);
        Log::info('file : ', ['file' => $validatedData['file']->getRealPath()]);

        try {
            $uploadedFile = cloudinary()
            ->uploadApi()
            ->upload($validatedData['file']->getRealPath(), [
                'folder' => 'paytrack/media',
                'resource_type' => 'auto'
            ]);

            $secureUrl = $uploadedFile['secure_url'];

            return $this->sendResponse(['url' => $secureUrl], 'File uploaded successfully.', 201);
        } catch (\Throwable $th) {
            return $this->sendError('File Upload Error.', ['error' => $th->getMessage()], 500);
        }
    }
}
