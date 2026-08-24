@extends('marketplacepipeline::layouts.master')

@section('title', 'Upload Proof - Order #{{ $order->id }}')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading">
        <h3>Upload Proof of Payment</h3>
    </div>
    <div class="panel-body">
        <p class="text-muted">
            Please upload a screenshot of your bank transfer proof of payment.
            <br>Validation typically takes up to 24 hours.
        </p>

        @if($order->payment && $order->payment->proof_path)
            <div class="alert alert-info">
                <h4>Proof already uploaded</h4>
                <p>Your proof of payment has already been received and is pending validation.</p>
                <img src="{{ asset('storage/' . $order->payment->proof_path) }}" alt="Proof of payment" class="img-preview">
            </div>
        @endif

        <form method="POST" action="{{ route('handle-upload-proof', ['order' => $order->id]) }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="proof_image">Proof Screenshot</label>
                <input type="file" name="proof_image" id="proof_image" class="form-input" accept="image/*" required>
                @error('proof_image') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                Upload Proof
            </button>

            <a href="{{ route('orders.index') }}" class="btn btn-link">Cancel</a>
        </form>

        <hr>

        <p class="text-muted small">
            Accepted formats: JPG, PNG, GIF<br>
            Maximum file size: 5MB
        </p>
    </div>
</div>
@endsection