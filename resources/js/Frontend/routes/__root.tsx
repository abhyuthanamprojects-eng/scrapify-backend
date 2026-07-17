import { Outlet, Link, createRootRoute, HeadContent, Scripts } from "@tanstack/react-router";
import { Toaster } from "@/components/ui/sonner";

import "../styles.css";
import appCss from "../styles.css?url";

function NotFoundComponent() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background px-4">
      <div className="max-w-md text-center">
        <h1 className="text-7xl font-bold text-foreground">404</h1>
        <h2 className="mt-4 text-xl font-semibold text-foreground">Page not found</h2>
        <p className="mt-2 text-sm text-muted-foreground">
          The page you're looking for doesn't exist or has been moved.
        </p>
        <div className="mt-6">
          <Link
            to="/"
            className="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
          >
            Go home
          </Link>
        </div>
      </div>
    </div>
  );
}

export const Route = createRootRoute({
  head: () => ({
    meta: [
      { charSet: "utf-8" },
      { name: "viewport", content: "width=device-width, initial-scale=1" },
      { title: "Scrapify — कबाड़ हटाओ, कैश पाओ" },
      { name: "description", content: "India's smartest doorstep scrap pickup. Sell scrap, kabadi, raddi online — local kawadi wala at your door. Book free pickup, get paid instantly." },
      { name: "keywords", content: "scrap pickup, sell scrap online, kabadi, kawadi, kabadiwala, kawadi wala, local kawadi, local kabadi, scrap kawadi, kabad, raddi, raddiwala, scrap dealer near me, scrap buyer, online kabadi, doorstep scrap pickup, sell old newspaper, sell old electronics, e-waste pickup, ewaste, e waste, sell ewaste online, ewaste recycling, electronic waste, metal scrap, iron scrap, plastic scrap, paper scrap, कबाड़, कबाड़ी, रद्दी, कबाड़ीवाला, स्क्रैप" },
      { name: "author", content: "Scrapify" },
      { property: "og:title", content: "Scrapify — कबाड़ हटाओ, कैश पाओ" },
      { property: "og:description", content: "Your local kawadi wala online — sell scrap, kabadi, raddi, ewaste from home. Doorstep scrap pickup, instant UPI payment. Book free pickup now." },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary" },
      { name: "twitter:site", content: "@Lovable" },
      { name: "twitter:title", content: "Scrapify — कबाड़ हटाओ, कैश पाओ" },
      { name: "twitter:description", content: "Your local kawadi wala online — sell scrap, kabadi, raddi, ewaste from home. Doorstep scrap pickup, instant UPI payment. Book free pickup now." },
      { property: "og:image", content: "https://pub-bb2e103a32db4e198524a2e9ed8f35b4.r2.dev/278cb4fc-1780-4527-9f3b-cc50460b1165/id-preview-ad094c87--98f928f3-596f-4b9a-855a-39956f6bf389.lovable.app-1776970592177.png" },
      { name: "twitter:image", content: "https://pub-bb2e103a32db4e198524a2e9ed8f35b4.r2.dev/278cb4fc-1780-4527-9f3b-cc50460b1165/id-preview-ad094c87--98f928f3-596f-4b9a-855a-39956f6bf389.lovable.app-1776970592177.png" },
    ],
    links: [
      {
        rel: "stylesheet",
        href: appCss,
      },
    ],
  }),
  component: RootComponent,
  notFoundComponent: NotFoundComponent,
});

function WhatsAppButton() {
  return (
    <a
      href="https://wa.me/919870291813"
      target="_blank"
      rel="noopener noreferrer"
      aria-label="Chat with us on WhatsApp"
      className="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] shadow-lg transition-transform hover:scale-110"
    >
      <svg viewBox="0 0 32 32" className="h-8 w-8 fill-white" aria-hidden="true">
        <path d="M16.004 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.26.594 4.466 1.72 6.412L3.2 28.8l6.556-1.688a12.74 12.74 0 0 0 6.244 1.626h.006c7.058 0 12.794-5.74 12.794-12.8 0-3.42-1.33-6.634-3.748-9.05a12.72 12.72 0 0 0-9.048-3.688zm0 23.376h-.004a10.6 10.6 0 0 1-5.41-1.482l-.388-.23-4.022 1.036 1.074-3.922-.254-.402a10.57 10.57 0 0 1-1.628-5.652c0-5.868 4.776-10.642 10.648-10.642 2.844 0 5.516 1.108 7.526 3.12a10.58 10.58 0 0 1 3.114 7.526c-.002 5.868-4.778 10.648-10.656 10.648zm5.838-7.97c-.32-.16-1.894-.934-2.188-1.042-.294-.106-.508-.16-.72.16-.214.32-.828 1.042-1.014 1.256-.186.214-.374.24-.694.08-.32-.16-1.352-.498-2.574-1.588-.952-.848-1.594-1.896-1.78-2.216-.186-.32-.02-.494.14-.652.144-.144.32-.374.48-.56.16-.188.214-.32.32-.534.106-.214.054-.4-.026-.56-.08-.16-.72-1.736-.988-2.376-.26-.624-.524-.54-.72-.55-.186-.008-.4-.01-.614-.01-.214 0-.56.08-.854.4-.294.32-1.12 1.094-1.12 2.67 0 1.576 1.148 3.098 1.308 3.312.16.214 2.258 3.448 5.472 4.834.764.33 1.36.528 1.826.676.768.244 1.466.21 2.018.128.616-.092 1.894-.774 2.16-1.522.268-.748.268-1.388.188-1.522-.08-.134-.294-.214-.614-.374z" />
      </svg>
    </a>
  );
}

function RootComponent() {
  return (
    <>
      <Outlet />
      <WhatsAppButton />
      <Toaster position="top-center" richColors />
    </>
  );
}
