import appStoreBadge from "@/assets/app-store-badge.png";
import googlePlayBadge from "@/assets/google-play-badge.png";

const googlePlayUrl =
  "https://play.google.com/store/apps/details?id=com.abhyuthanam.scrapify&pcampaignid=web_share";

const appStoreUrl = "https://apps.apple.com/us/app/scrapify/id6775160804";

export function StoreBadges({ className = "" }: { className?: string }) {
  return (
    <div className={`flex flex-wrap items-center justify-center gap-6 py-4 ${className}`}>
      <a
        href={appStoreUrl}
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Download Scrapify on the App Store"
        className="block transition-all hover:scale-105 active:scale-95"
      >
        <img 
          src={appStoreBadge} 
          alt="Download on the App Store" 
          className="h-10 md:h-12 w-auto object-contain drop-shadow-sm"
        />
      </a>
      <a
        href={googlePlayUrl}
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Get Scrapify on Google Play"
        className="block transition-all hover:scale-105 active:scale-95"
      >
        <img 
          src={googlePlayBadge} 
          alt="Get it on Google Play" 
          className="h-10 md:h-12 w-auto object-contain drop-shadow-sm"
        />
      </a>
    </div>
  );
}
