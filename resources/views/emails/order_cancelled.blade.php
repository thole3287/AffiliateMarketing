<!-- resources/views/emails/order_cancelled.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <title>Đơn hàng đã được hủy</title>
</head>
<body>
    <h1>Đơn hàng #{{ $order->id }} đã được hủy</h1>
    <p>Xin chào {{ $order->user->name }},</p>
    <p>Đơn hàng #{{ $order->id }} của bạn đã được hủy thành công.</p>
    <p>Chi tiết đơn hàng:</p>
    <ul>
        <li>Tổng tiền: {{ $order->total_amount }}</li>
        <li>Phương thức thanh toán: {{ $order->payment_method }}</li>
        <li>Trạng thái thanh toán: {{ $order->payment_status }}</li>
    </ul>
    <p>Nếu có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>
    <p>Trân trọng,</p>
    <p>Đội ngũ hỗ trợ</p>
</body>
</html>
