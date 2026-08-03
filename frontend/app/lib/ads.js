// Single place to swap in real banner images/links per slot. Drop a new
// image into public/ads/ and point `src` at it - set `src: null` to hide a
// slot entirely (AdBanner renders nothing when there's no image).
export const AD_SLOTS = {
  home: { src: "/ads/placeholder-banner.svg", href: null, alt: "Advertisement" },
  ingame: { src: "/ads/placeholder-banner.svg", href: null, alt: "Advertisement" },
};
