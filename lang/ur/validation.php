<?php

// Urdu versions of the standard validation messages used in this app.
// Numbers stay as Western digits on purpose (clinical safety: no misread vitals).
return [
    'required' => ':attribute لازمی ہے۔',
    'string' => ':attribute متن ہونا چاہیے۔',
    'integer' => ':attribute پورا عدد ہونا چاہیے۔',
    'numeric' => ':attribute عدد ہونا چاہیے۔',
    'date' => ':attribute درست تاریخ نہیں ہے۔',
    'email' => ':attribute درست ای میل نہیں ہے۔',
    'in' => 'منتخب :attribute درست نہیں ہے۔',
    'digits' => ':attribute میں :digits ہندسے ہونے چاہییں۔',
    'unique' => 'یہ :attribute پہلے سے موجود ہے۔',
    'regex' => ':attribute کی شکل درست نہیں ہے۔',
    'before_or_equal' => ':attribute آج یا اس سے پہلے کی ہونی چاہیے۔',
    'after' => ':attribute :date کے بعد کی ہونی چاہیے۔',
    'between' => [
        'numeric' => ':attribute :min اور :max کے درمیان ہونا چاہیے۔',
    ],
    'min' => [
        'string' => ':attribute کم از کم :min حروف کا ہونا چاہیے۔',
    ],
    'max' => [
        'string' => ':attribute :max حروف سے زیادہ نہیں ہو سکتا۔',
    ],
    'lt' => [
        'numeric' => ':attribute، :value سے کم ہونا چاہیے۔',
    ],

    'attributes' => [
        'full_name' => 'پورا نام',
        'date_of_birth' => 'تاریخِ پیدائش',
        'gender' => 'جنس',
        'phone' => 'موبائل فون',
        'cnic' => 'شناختی کارڈ نمبر',
        'systolic_bp' => 'BP سسٹولک',
        'diastolic_bp' => 'BP ڈائسٹولک',
        'pulse' => 'نبض',
        'temperature' => 'درجہ حرارت',
        'spo2' => 'SpO2',
        'respiratory_rate' => 'سانس کی رفتار',
        'consciousness' => 'ہوش کی حالت',
        'chief_complaint' => 'بنیادی شکایت',
        'pain_score' => 'درد کا اسکور',
        'allergies' => 'الرجی',
        'fall_risk' => 'گرنے کا خطرہ',
        'email' => 'ای میل',
        'password' => 'پاس ورڈ',
    ],
];
