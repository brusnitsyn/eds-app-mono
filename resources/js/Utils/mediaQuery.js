import {useMediaQuery} from "@vueuse/core";
import {LGScreen, MDScreen, SMScreen, XLScreen} from "@/constants.js";

export const isSmallScreen = useMediaQuery(SMScreen)
export const isMediumScreen = useMediaQuery(MDScreen)
export const isLargeScreen = useMediaQuery(LGScreen)
export const isExtraLargeScreen = useMediaQuery(XLScreen)
