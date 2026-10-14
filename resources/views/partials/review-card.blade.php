<blockquote class="testimonial-marquee-card testimonial-card">
    <p class="text-sm leading-relaxed text-sharm-mist">&ldquo;{{ $review->comment }}&rdquo;</p>
    <footer class="mt-4 border-t border-sharm-deep/5 pt-4">
        <cite class="not-italic">
            <span class="block text-sm font-semibold text-sharm-deep">{{ $review->name }}</span>
            <span class="mt-0.5 flex items-center gap-1.5 text-xs text-sharm-mist">
                @if ($review->flag_code)
                    <span class="fi fi-{{ $review->flag_code }} rounded-sm"></span>
                @endif
                <span>{{ $review->country }}</span>
            </span>
        </cite>
    </footer>
</blockquote>
