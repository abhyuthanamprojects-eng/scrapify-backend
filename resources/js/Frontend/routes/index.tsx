import { createFileRoute } from "@tanstack/react-router";
import { Navbar } from "@/components/site/Navbar";
import { Hero } from "@/components/site/Hero";
import { Stats } from "@/components/site/Stats";
import { Services } from "@/components/site/Services";
import { BookPickup } from "@/components/site/BookPickup";
import { HowItWorks } from "@/components/site/HowItWorks";
import { Reviews } from "@/components/site/Reviews";
import { FAQ } from "@/components/site/FAQ";
import { DownloadCTA } from "@/components/site/DownloadCTA";
import { Footer } from "@/components/site/Footer";

export const Route = createFileRoute("/")({
  component: Index,
  head: () => ({
    meta: [
      { title: "Scrapify — कबाड़ हटाओ, कैश पाओ | Doorstep Scrap Pickup" },
      {
        name: "description",
        content:
          "Scrapify — your local kabadi wala online. Sell scrap, raddi, old newspaper, ewaste, metal & plastic scrap from home. Doorstep kawadi pickup, instant UPI payment. Powered by Abhyuthanam Industries Pvt. Ltd.",
      },
      { property: "og:title", content: "Scrapify — Turn your scrap into cash in minutes" },
      {
        property: "og:description",
        content:
          "Local kawadi at your doorstep — sell kabadi, raddi, ewaste & scrap online. कबाड़ हटाओ, कैश पाओ — book a pickup in seconds.",
      },
      { property: "og:type", content: "website" },
    ],
  }),
});

function Index() {
  return (
    <main className="min-h-screen bg-background">
      <Navbar />
      <Hero />
      <Stats />
      <Services />
      {/* <BookPickup /> */}
      <HowItWorks />
      <Reviews />
      <FAQ />
      <DownloadCTA />
      <Footer />
    </main>
  );
}
