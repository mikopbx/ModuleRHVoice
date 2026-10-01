# Builds the native RHVoice engine + CLI for the current --platform
# (used for both linux/amd64 and linux/arm64 via buildx/QEMU).
#
# Output layout in /out:
#   /out/bin/RHVoice-test            CLI (arch-specific)
#   /out/lib/libRHVoice*.so*         shared libs (arch-specific)
#   /out/data/languages/{Russian,Uzbek}   language data (arch-independent)
#   /out/data/voices/tatiana         preloaded voice (arch-independent)
#   /out/etc/{dicts,RHVoice.conf}    config (arch-independent)
#
# Built on Debian bookworm (glibc 2.36 <= MikoPBX 2.38) with a statically linked
# C++ runtime so the binary does not depend on the host libstdc++ ABI.
FROM debian:bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git g++ make scons pkg-config ca-certificates file binutils \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /work
RUN git clone --depth 1 https://github.com/RHVoice/RHVoice.git
WORKDIR /work/RHVoice

# Third-party deps (bundled boost subset, sonic, etc.).
RUN git submodule update --init --recursive external

# Only the languages we ship and the preloaded voice.
RUN git submodule update --init data/languages/Russian data/languages/Uzbek
RUN git submodule update --init data/voices/tatiana

# dev=1 => runnable from the build dir (builds the RHVoice-test CLI, no global install).
ENV CXXFLAGS="-O2"
ENV LINKFLAGS="-static-libstdc++ -static-libgcc"
RUN scons -j"$(nproc)" dev=1 languages=Russian,Uzbek voices=tatiana

RUN set -eux; \
    mkdir -p /out/bin /out/lib /out/data/languages /out/data/voices /out/etc; \
    cp local/bin/RHVoice-test /out/bin/; \
    cp -a local/lib/libRHVoice_core.so*  /out/lib/; \
    cp -a local/lib/libRHVoice_audio.so* /out/lib/; \
    cp -a local/lib/libRHVoice.so*       /out/lib/; \
    cp -a data/languages/Russian data/languages/Uzbek /out/data/languages/; \
    cp -a data/voices/tatiana /out/data/voices/; \
    cp -a config/dicts /out/etc/; \
    cp -a config/RHVoice.conf /out/etc/ 2>/dev/null || true; \
    strip /out/bin/RHVoice-test /out/lib/*.so* 2>/dev/null || true; \
    ls -la /out/bin /out/lib
