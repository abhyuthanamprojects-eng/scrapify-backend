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
          "Scrapify is India's smartest doorstep scrap collection app. Book a free pickup, get paid instantly via UPI. Powered by Abhyuthanam Industries Pvt. Ltd.",
      },
      { property: "og:title", content: "Scrapify — Turn your scrap into cash in minutes" },
      {
        property: "og:description",
        content:
          "On-demand professional scrap collection. कबाड़ हटाओ, कैश पाओ — book a pickup in seconds.",
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
