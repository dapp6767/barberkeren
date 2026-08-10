'use client';

import React, { useRef } from 'react';
import { motion, useScroll, useTransform } from 'framer-motion';
import Image from 'next/image';

interface StickySlideUpHeroProps {
  title: string;
  subtitle: string;
  heroImageSrc: string;
  nextSectionImageSrc: string;
  children: React.ReactNode;
}

export default function StickySlideUpHero({
  title,
  subtitle,
  heroImageSrc,
  nextSectionImageSrc,
  children,
}: StickySlideUpHeroProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  
  // Track scroll progress of the container
  const { scrollYProgress } = useScroll({
    target: containerRef,
    offset: ["start start", "end end"]
  });

  // Optional parallax effect for the hero image and text
  const heroOpacity = useTransform(scrollYProgress, [0, 0.5], [1, 0.3]);
  const heroScale = useTransform(scrollYProgress, [0, 0.5], [1, 0.95]);
  const heroY = useTransform(scrollYProgress, [0, 0.5], ['0%', '10%']);

  return (
    <div ref={containerRef} className="relative w-full">
      
      {/* 1. STICKY HERO SECTION */}
      <div className="sticky top-0 h-screen w-full overflow-hidden z-10 bg-black">
        <motion.div 
          className="absolute inset-0 w-full h-full"
          style={{ 
            opacity: heroOpacity,
            scale: heroScale,
            y: heroY
          }}
        >
          {/* Background Image for Hero */}
          <Image
            src={heroImageSrc}
            alt="Hero Background"
            fill
            className="object-cover opacity-60"
            priority
          />
          <div className="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/80" />
          
          {/* Hero Content */}
          <div className="absolute inset-0 flex flex-col items-center justify-center text-center p-6 mt-16">
            <motion.h1 
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ duration: 0.8, ease: "easeOut" }}
              className="text-5xl md:text-7xl lg:text-8xl font-extrabold tracking-tight text-white mb-4"
            >
              {title}
            </motion.h1>
            
            <motion.p 
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ duration: 0.8, ease: "easeOut", delay: 0.2 }}
              className="text-xl md:text-3xl text-zinc-300 font-medium"
            >
              {subtitle}
            </motion.p>
            
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 1, duration: 1 }}
              className="absolute bottom-12 flex flex-col items-center"
            >
              <span className="text-sm text-zinc-400 mb-2 uppercase tracking-widest">Gulir untuk melihat layanan</span>
              <motion.div
                animate={{ y: [0, 10, 0] }}
                transition={{ repeat: Infinity, duration: 1.5 }}
                className="w-6 h-10 border-2 border-zinc-500 rounded-full flex justify-center p-1"
              >
                <div className="w-1 h-2 bg-zinc-400 rounded-full" />
              </motion.div>
            </motion.div>
          </div>
        </motion.div>
      </div>

      {/* 2. SLIDE-UP NEXT SECTION */}
      {/* This section sits in the normal document flow but appears below the 100vh hero.
          As the user scrolls down, this div slides up and covers the sticky hero. */}
      <div className="relative z-20 w-full min-h-screen flex flex-col items-center justify-center shadow-[0_-20px_50px_rgba(0,0,0,0.5)] rounded-t-3xl sm:rounded-t-[3rem] overflow-hidden">
        {/* Background image for the next section (replacing the solid/white background) */}
        <div className="absolute inset-0 -z-10 bg-black">
            <Image
                src={nextSectionImageSrc}
                alt="Next Section Background"
                fill
                className="object-cover opacity-30"
            />
            {/* Overlay so the text is readable */}
            <div className="absolute inset-0 bg-zinc-950/70 backdrop-blur-md" />
        </div>
        
        <div className="w-full max-w-4xl mx-auto px-6 py-24 z-10 text-center flex flex-col items-center">
          {children}
        </div>
      </div>
      
    </div>
  );
}
