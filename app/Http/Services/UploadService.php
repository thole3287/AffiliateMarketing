<?php

namespace App\Http\Services;

use Illuminate\Http\Request;


class UploadService
{
    public function updateSingleImage(Request $request, $inputNameFile, $inputNameUrl, $folderName, $checkSightengine)
    {
        $imageUrl = null; // Khởi tạo $imageUrl với giá trị mặc định
        if ($request->hasFile($inputNameFile)) {

            if ($checkSightengine) {
                $image = $request->file($inputNameFile);
                $imageName = time() . '_' . $image->getClientOriginalName();
                // Gửi hình ảnh đến API của Sightengine để kiểm tra
                $params = array(
                    'media' => new \CurlFile($image->getPathname()),
                    'models' => 'nudity-2.0,wad,text-content,gambling',
                    'api_user' => '613442916',
                    'api_secret' => 'WB7KoC3TpJdDFJXbj3RNijhDBx7V4tT2',
                );
                // Gửi yêu cầu kiểm tra
                $ch = curl_init('https://api.sightengine.com/1.0/check.json');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                $response = curl_exec($ch);
                curl_close($ch);

                $output = json_decode($response, true);
                // Kiểm tra kết quả từ API
                if ($output['status'] === 'success' && $output['nudity']['sexual_activity'] <= 0.5) {
                    // Nếu hình ảnh không chứa nội dung không phù hợp, lưu hình ảnh vào thư mục và cơ sở dữ liệu
                    $image->move(public_path($folderName), $imageName);
                    $imageUrl = 'public/' . $folderName . '/' . $imageName;
                } else {
                    // Nếu hình ảnh chứa nội dung không phù hợp, không lưu và gửi thông báo cho người dùng
                    return response()->json([
                        'status' => false,
                        'message' => 'Hình ảnh không phù hợp. Vui lòng thử lại với hình ảnh khác.'
                    ], 400);
                }
            } else {
                $image = $request->file($inputNameFile);
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path($folderName), $imageName); // Di chuyển ảnh vào thư mục public
                $imageUrl = 'public/' . $folderName . '/' . $imageName; // Lấy đường dẫn URL của ảnh
            }
        } elseif ($request->filled($inputNameUrl)) {
            if($checkSightengine)
            {
                $imageUrl = $request->input($inputNameUrl);
                $params = array(
                    'url' =>  $imageUrl,
                    'models' => 'nudity-2.0,wad',
                    'api_user' => '613442916',
                    'api_secret' => 'WB7KoC3TpJdDFJXbj3RNijhDBx7V4tT2',
                  );

                  $ch = curl_init('https://api.sightengine.com/1.0/check.json?'.http_build_query($params));
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                  $response = curl_exec($ch);
                  curl_close($ch);

                  $output = json_decode($response, true);
                  if ($output['status'] === 'success' && $output['nudity']['sexual_activity'] <= 0.5) {
                    // Nếu hình ảnh không chứa nội dung không phù hợp, lưu hình ảnh vào thư mục và cơ sở dữ liệu
                    $imageUrl = $request->input($inputNameUrl);
                } else {
                    // Nếu hình ảnh chứa nội dung không phù hợp, không lưu và gửi thông báo cho người dùng
                    return response()->json([
                        'status' => false,
                        'message' => 'Hình ảnh không phù hợp. Vui lòng thử lại với hình ảnh khác.'
                    ], 400);
                }
            }else
            {
                $imageUrl = $request->input($inputNameUrl);
            }
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
