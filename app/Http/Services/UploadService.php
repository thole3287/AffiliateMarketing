<?php

namespace App\Http\Services;

use Illuminate\Http\Request;


class UploadService
{
    public function updateSingleImage(Request $request, $inputNameFile, $inputNameUrl, $folderName)
    {
        $imageUrl = null;

        // Upload image if provided via file input
        if ($request->hasFile($inputNameFile)) {
            $image = $request->file($inputNameFile);
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path($folderName), $imageName); // Di chuyển ảnh vào thư mục public
            $imageUrl = 'public/' . $folderName . '/' . $imageName; // Lấy đường dẫn URL của ảnh
        } elseif ($request->filled($inputNameUrl)) {
            // Use provided URL if file input is empty
            $imageUrl = $request->input($inputNameUrl);
        }

        return $imageUrl; // Trả về đường dẫn URL của ảnh
    }


    public function uploadMultipleImages(Request $request, $inputNameFile, $inputNameUrl, $folderName)
    {
        $images = [];

        if ($request->hasFile($inputNameFile)) {
            foreach ($request->file($inputNameFile) as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public/' . $folderName, $imageName);
                $images[] = 'public/' . $folderName . '/' . $imageName; // Thêm đường dẫn vào mảng
            }
        }

        if ($request->input($inputNameUrl)) {
            foreach ($request->input($inputNameUrl) as $image_url) {
                // Kiểm tra xem địa chỉ URL có hợp lệ không trước khi thêm vào mảng
                if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $images[] = $image_url;
                }
            }
        }

        return $images; // Trả về mảng đường dẫn của các ảnh đã upload
    }
}
