@props(['banner'])
{{-- Mobile / tablet: the banner fills a short strip (cropped). Desktop (lg): a 300px band where the
same image is drawn twice — behind, stretched to cover and darkened by an overlay; in front, at its
natural proportions (never stretched, only shrunk to fit the band) so nothing is cropped. --}}
<img src="{{ $banner->image_url }}" alt="{{ $banner->title ?: 'Banner' }}"
    class="h-[130px] w-full object-cover sm:h-44 lg:hidden" />
<div class="relative hidden h-[300px] w-full items-center justify-center overflow-hidden bg-black lg:flex">
    <img src="{{ $banner->image_url }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover" />
    <div aria-hidden="true" class="absolute inset-0 bg-black/60"></div>
    <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?: 'Banner' }}" class="relative h-auto max-h-full w-auto max-w-full" />
</div>
