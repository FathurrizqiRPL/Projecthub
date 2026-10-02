@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' =>
            'w-full rounded-xl border-slate-300 bg-[#fffdfa]
            text-slate-900 placeholder:text-slate-400
            shadow-sm
            focus:border-red-500 focus:ring-red-500
            disabled:cursor-not-allowed disabled:bg-slate-100
            transition'
    ]) }}
>
