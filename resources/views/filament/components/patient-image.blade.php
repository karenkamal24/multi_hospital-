@php
    $record = $getRecord();
    $user = $record?->user;
    $imageUrl = $user && $user->image ? asset('storage/' . $user->image) : null;
@endphp

@if($imageUrl)
    <div style="text-align: center; margin-bottom: 15px;">
        <img src="{{ $imageUrl }}"
             alt="صورة هوية المريض"
             style="max-width: 200px;
                    height: 200px;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 3px solid #e5e7eb;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" />
    </div>
@else
    <div style="text-align: center; margin-bottom: 15px; color: #9ca3af;">
        <p>لا توجد صورة</p>
    </div>
@endif



