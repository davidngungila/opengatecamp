<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
</head>
<body>
@include('digital-cards.pdf-body', ['card' => $card, 'qrData' => $qrData, 'recipientName' => $recipientName ?? null, 'publicUrl' => $publicUrl ?? null])
</body>
</html>