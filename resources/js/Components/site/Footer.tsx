import { Twitter, Instagram, Linkedin, Mail, Facebook, Youtube } from "lucide-react";
import { Link } from "@tanstack/react-router";
import logo from "@/assets/scrapify-logo-04.svg";

export function Footer() {
  return (
    <footer className="border-t border-border bg-card">
      <div className="mx-auto w-[min(1200px,94%)] py-14">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-10">
          <div className="col-span-2">
            <img src={logo} alt="Scrapify" className="h-16 w-auto" />
            <div className="mt-5 flex gap-2">
              {[
                { Icon: Twitter, href: "https://x.com/_scrapify" },
                { Icon: Instagram, href: "https://www.instagram.com/kabadhataocashpao?igsh=cWRpcmNpeXpkaHA4" },
                { Icon: Linkedin, href: "https://www.linkedin.com/company/scrapify-%E0%A4%95%E0%A4%AC%E0%A4%BE%E0%A4%B1-%E0%A4%B9%E0%A4%9F%E0%A4%BE%E0%A4%93-%E0%A4%95%E0%A5%88%E0%A4%B6-%E0%A4%AA%E0%A4%BE%E0%A4%93/" },
                { Icon: Facebook, href: "https://www.facebook.com/profile.php?id=61589460632983" },
                { Icon: Youtube, href: "https://www.youtube.com/@scrapify" },
                { Icon: Mail, href: "mailto:amitsinhadeveloper@gmail.com" },
              ].map(({ Icon, href }, i) => (
                <a
                  key={i}
                  href={href}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex h-10 w-10 items-center justify-center rounded-full bg-secondary text-primary-deep hover:gradient-primary hover:text-primary-foreground transition-spring"
                >
                  <Icon className="h-4 w-4" />
                </a>
              ))}
            </div>
          </div>

          <div>
            <p className="text-xs font-bold uppercase tracking-widest text-navy">Company</p>
            <ul className="mt-4 space-y-2 text-sm text-muted-foreground">
              <li><a href="#about" className="hover:text-primary-deep">About Us</a></li>
              <li><Link to="/contact" className="hover:text-primary-deep">Contact Us</Link></li>
              <li><Link to="/partner" className="hover:text-primary-deep">Become a Partner</Link></li>
              <li><Link to="/warehouses" className="hover:text-primary-deep">Our Warehouses</Link></li>
            </ul>
          </div>

          <div>
            <p className="text-xs font-bold uppercase tracking-widest text-navy">Legal</p>
            <ul className="mt-4 space-y-2 text-sm text-muted-foreground">
              <li><Link to="/terms" className="hover:text-primary-deep">Terms & Conditions</Link></li>
              <li><Link to="/privacy" className="hover:text-primary-deep">Privacy Policy</Link></li>
              <li><Link to="/cancellation" className="hover:text-primary-deep">Cancellation Policy</Link></li>
            </ul>
          </div>
        </div>

        <div className="mt-10 flex flex-col md:flex-row items-center justify-between gap-3 border-t border-border pt-6 text-xs text-muted-foreground">
          <p>© {new Date().getFullYear()} Scrapify. All rights reserved.</p>
          <p>Made with ♻ in India</p>
        </div>
      </div>
    </footer>
  );
}
