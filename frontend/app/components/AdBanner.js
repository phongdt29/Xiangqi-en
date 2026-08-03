import { AD_SLOTS } from "../lib/ads";

export default function AdBanner({ slot }) {
  const ad = AD_SLOTS[slot];
  if (!ad?.src) return null;

  const image = <img src={ad.src} alt={ad.alt} className="ad-banner-img" />;

  return (
    <div className="ad-banner text-center my-3">
      {ad.href ? (
        <a href={ad.href} target="_blank" rel="noopener noreferrer sponsored">
          {image}
        </a>
      ) : (
        image
      )}
    </div>
  );
}
