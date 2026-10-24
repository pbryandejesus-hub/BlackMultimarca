@props(['amount'])

<span {{ $attributes }}>₲ {{ number_format((int) $amount, 0, ',', '.') }}</span>
