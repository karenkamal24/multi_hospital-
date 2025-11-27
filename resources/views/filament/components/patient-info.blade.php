@php
    $record = $getRecord();
    $user = $record?->user;
    $imageUrl = $user && $user->image ? asset('storage/' . $user->image) : null;
@endphp

<div style="padding: 20px; background: #f9fafb; border-radius: 8px; margin-bottom: 20px;">
    <h3 style="margin-bottom: 15px; color: #1f2937; font-size: 18px; font-weight: 600;">معلومات المريض</h3>

    @if($imageUrl)
        <div style="text-align: center; margin-bottom: 20px;">
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
        <div style="text-align: center; margin-bottom: 20px; color: #9ca3af;">
            <p>لا توجد صورة</p>
        </div>
    @endif

    @if($user)
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div>
                <strong style="color: #6b7280;">الاسم:</strong>
                <p style="margin: 5px 0; color: #1f2937;">{{ $user->name }}</p>
            </div>
            <div>
                <strong style="color: #6b7280;">الهاتف:</strong>
                <p style="margin: 5px 0; color: #1f2937;">{{ $user->phone ?? 'غير محدد' }}</p>
            </div>
            <div>
                <strong style="color: #6b7280;">البريد الإلكتروني:</strong>
                <p style="margin: 5px 0; color: #1f2937;">{{ $user->email ?? 'غير محدد' }}</p>
            </div>
            <div>
                <strong style="color: #6b7280;">فصيلة الدم:</strong>
                <p style="margin: 5px 0; color: #1f2937;">
                    <span style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                        {{ $user->blood ?? 'غير محدد' }}
                    </span>
                </p>
            </div>
        </div>
    @else
        <p style="color: #9ca3af;">لا توجد معلومات</p>
    @endif
</div>



