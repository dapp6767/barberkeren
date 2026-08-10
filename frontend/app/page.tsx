import StickySlideUpHero from "@/components/ui/sticky-slide-up-hero"
import { Button } from "@/components/ui/button"

export default function Home() {
    return (
        <div className="min-h-screen bg-zinc-950 text-white">
            <StickySlideUpHero
                title="ELITE BARBER"
                subtitle="Pangkas Rambut Profesional"
                heroImageSrc="https://images.unsplash.com/photo-1585747860715-2ba37e788b70?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80"
                nextSectionImageSrc="https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80"
            >
                <p className="text-lg md:text-2xl text-zinc-300 mb-10 text-balance leading-relaxed">
                    Tempat yang menyediakan layanan potong rambut, penataan rambut, pencukuran jenggot dan kumis, serta berbagai perawatan penampilan khusus pria. Tempat ini mengutamakan pelayanan profesional, kenyamanan pelanggan, kebersihan, dan kualitas hasil perawatan sehingga pelanggan memperoleh pengalaman yang memuaskan.
                </p>
                <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Button asChild size="lg" className="h-12 rounded-full px-8 text-base bg-white text-zinc-950 hover:bg-zinc-200 shadow-[0_0_20px_rgba(255,255,255,0.2)]">
                        <a href="/dashboard.php">Book Now</a>
                    </Button>
                    <Button asChild size="lg" variant="outline" className="h-12 rounded-full px-8 text-base border-zinc-500 text-white hover:bg-zinc-800 hover:text-white bg-black/30 backdrop-blur-md">
                        <a href="#services">Lihat Ulasan</a>
                    </Button>
                </div>
            </StickySlideUpHero>
        </div>
    )
}
